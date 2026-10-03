<?php

namespace App\Services;

use App\Models\MockSession;
use App\Models\Option;
use App\Models\Question;
use App\Models\QuizAttempt;
use App\Models\UserAnswer;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MockExamService
{
    /**
     * Persist the final answer set and calculate the result from server-side data.
     *
     * The question order, available options, correct answers and timer are never
     * trusted from the browser. A completion is idempotent: an already completed
     * attempt is returned unchanged.
     */
    public function complete(MockSession $session, array $answers = []): QuizAttempt
    {
        return DB::transaction(function () use ($session, $answers): QuizAttempt {
            $session = MockSession::query()
                ->with('quizAttempt')
                ->whereKey($session->id)
                ->where('user_id', $session->user_id)
                ->lockForUpdate()
                ->firstOrFail();

            $attempt = $session->quizAttempt;

            if (! $attempt) {
                throw ValidationException::withMessages([
                    'session' => ['This mock session has no quiz attempt.'],
                ]);
            }

            $attempt = QuizAttempt::query()
                ->whereKey($attempt->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($attempt->status === 'completed') {
                return $attempt;
            }

            $questionIds = array_values(array_map('intval', $attempt->question_order ?? []));
            if ($questionIds === []) {
                throw ValidationException::withMessages([
                    'session' => ['This mock session has no question order.'],
                ]);
            }

            $this->validateAnswers($questionIds, $answers);

            $questions = Question::query()
                ->whereIn('id', $questionIds)
                ->with('options')
                ->get()
                ->keyBy('id');

            $storedAnswers = UserAnswer::query()
                ->where('quiz_attempt_id', $attempt->id)
                ->get()
                ->keyBy('question_id');

            $correctCount = 0;
            $answeredCount = 0;

            foreach ($questionIds as $questionId) {
                $question = $questions->get($questionId);
                if (! $question) {
                    continue;
                }

                // Final request state wins; existing DB progress is retained when
                // a client only submits a partial answer map.
                $optionId = array_key_exists($questionId, $answers)
                    ? ($answers[$questionId] === null ? null : (int) $answers[$questionId])
                    : ($storedAnswers->get($questionId)?->option_id);

                $selectedOption = $optionId !== null
                    ? $question->options->firstWhere('id', $optionId)
                    : null;

                $isCorrect = (bool) ($selectedOption?->is_correct ?? false);

                if ($optionId !== null) {
                    $answeredCount++;
                }

                if ($isCorrect) {
                    $correctCount++;
                }

                UserAnswer::query()->updateOrCreate(
                    [
                        'quiz_attempt_id' => $attempt->id,
                        'question_id' => $questionId,
                    ],
                    [
                        'user_id' => $session->user_id,
                        'mock_session_id' => $session->id,
                        'option_id' => $optionId,
                        'is_correct' => $isCorrect,
                    ]
                );
            }

            $totalQuestions = count($questionIds);
            $percentage = $totalQuestions > 0
                ? round(($correctCount / $totalQuestions) * 100, 2)
                : 0;

            $startedAt = $attempt->started_at ?? $session->created_at ?? now();
            $timeTaken = min(
                max(0, (int) $session->time_limit * 60),
                max(0, (int) $startedAt->diffInSeconds(now()))
            );

            $attempt->update([
                'score' => $correctCount,
                'correct_answers' => $correctCount,
                'total_questions' => $totalQuestions,
                'answered_questions' => $answeredCount,
                'percentage' => $percentage,
                'score_percentage' => $percentage,
                'time_taken_seconds' => $timeTaken,
                'time_spent_seconds' => $timeTaken,
                'completed_at' => now(),
                'status' => 'completed',
            ]);

            $session->update(['status' => 'completed']);

            return $attempt->fresh();
        });
    }

    private function validateAnswers(array $questionIds, array $answers): void
    {
        $allowed = array_fill_keys($questionIds, true);

        foreach ($answers as $questionId => $optionId) {
            $questionId = (int) $questionId;

            if (! isset($allowed[$questionId])) {
                throw ValidationException::withMessages([
                    'answers' => ['An answer references a question outside this mock session.'],
                ]);
            }

            if ($optionId !== null && ! Option::query()
                ->whereKey((int) $optionId)
                ->where('question_id', $questionId)
                ->exists()) {
                throw ValidationException::withMessages([
                    'answers' => ['A selected option does not belong to its question.'],
                ]);
            }
        }
    }
}
