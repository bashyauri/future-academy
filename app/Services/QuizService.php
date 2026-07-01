<?php

namespace App\Services;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Models\UserAnswer;
use Illuminate\Support\Facades\DB;

class QuizService
{
    /**
     * Get available quizzes with optional filters
     * Uses Quiz model with JSON_CONTAINS for subject filtering (like web app)
     */
    public function getQuizzes(?int $subjectId = null, ?string $type = null): array
    {
        $query = Quiz::active()
            ->available()
            ->with(['subject', 'creator']);

        if ($subjectId) {
            $query->whereRaw('JSON_CONTAINS(subject_ids, ?)', [json_encode([$subjectId])]);
        }

        if ($type) {
            $query->where('type', $type);
        }

        return $query->get()->map(function ($quiz) {
            return [
                'id' => $quiz->id,
                'title' => $quiz->title,
                'description' => $quiz->description,
                'type' => $quiz->type,
                'duration_minutes' => $quiz->duration_minutes,
                'question_count' => $quiz->question_count,
                'is_free' => $quiz->is_free,
                'subject' => $quiz->subject ? [
                    'id' => $quiz->subject->id,
                    'name' => $quiz->subject->name,
                    'code' => $quiz->subject->code,
                ] : null,
            ];
        })->toArray();
    }

    /**
     * Get quiz details with questions
     */
    public function getQuizDetails(int $quizId): array
    {
        $quiz = Quiz::with(['subject', 'questions.options'])->findOrFail($quizId);

        return [
            'id' => $quiz->id,
            'title' => $quiz->title,
            'description' => $quiz->description,
            'type' => $quiz->type,
            'duration_minutes' => $quiz->duration_minutes,
            'question_count' => $quiz->question_count,
            'subject' => $quiz->subject ? [
                'id' => $quiz->subject->id,
                'name' => $quiz->subject->name,
                'code' => $quiz->subject->code,
            ] : null,
            'questions' => $quiz->questions->map(function ($question) {
                return [
                    'id' => $question->id,
                    'question_text' => $question->question_text,
                    'question_image' => $question->question_image,
                    'explanation' => $question->explanation,
                    'options' => $question->options->map(function ($option) {
                        return [
                            'id' => $option->id,
                            'label' => $option->label,
                            'option_text' => $option->option_text,
                            'option_image' => $option->option_image,
                        ];
                    })->toArray(),
                ];
            })->toArray(),
        ];
    }

    /**
     * Start a new quiz attempt
     */
    public function startQuiz(User $user, int $quizId, array $options = []): QuizAttempt
    {
        return DB::transaction(function () use ($user, $quizId, $options) {
            $quiz = Quiz::findOrFail($quizId);

            $questionCount = $options['question_count'] ?? $quiz->question_count;
            $shuffle = $options['shuffle'] ?? false;
            $timeLimitMinutes = $options['time_limit'] ?? null;
            $timeLimitSeconds = $timeLimitMinutes ? ((int) $timeLimitMinutes * 60) : 0;

            $query = $quiz->questions();

            if ($shuffle) {
                $query->inRandomOrder();
            }

            $questions = $query
                ->limit($questionCount)
                ->pluck('questions.id')
                ->toArray();

            return QuizAttempt::create([
                'user_id' => $user->id,
                'quiz_id' => $quizId,
                'subject_id' => $quiz->subject_id,
                'exam_year' => $quiz->exam_year,
                'status' => 'in_progress',
                'started_at' => now(),
                'time_taken_seconds' => $timeLimitSeconds,
                'total_questions' => count($questions),
                'question_order' => $questions,
            ]);
        });
    }

    /**
     * Submit answers for a quiz attempt
     */
    public function submitAnswers(User $user, int $attemptId, array $answers): array
    {
        return DB::transaction(function () use ($user, $attemptId, $answers) {
            $attempt = QuizAttempt::where('user_id', $user->id)
                ->where('id', $attemptId)
                ->where('status', 'in_progress')
                ->firstOrFail();

            $correctCount = 0;
            foreach ($answers as $answer) {
                $question = $attempt->quiz->questions()->find($answer['question_id']);
                if (! $question) {
                    continue;
                }

                $selectedOption = $question->options()->find($answer['option_id']);
                $isCorrect = $selectedOption?->is_correct ?? false;

                if ($isCorrect) {
                    $correctCount++;
                }

                UserAnswer::updateOrCreate(
                    [
                        'quiz_attempt_id' => $attemptId,
                        'question_id' => $answer['question_id'],
                    ],
                    [
                        'option_id' => $answer['option_id'],
                        'is_correct' => $isCorrect,
                        'time_spent_seconds' => $answer['time_spent_seconds'] ?? 0,
                    ]
                );
            }

            $scorePercentage = ($correctCount / $attempt->total_questions) * 100;
            $passed = $scorePercentage >= 50;

            $attempt->update([
                'status' => 'completed',
                'completed_at' => now(),
                'time_taken_seconds' => now()->diffInSeconds($attempt->started_at),
                'correct_answers' => $correctCount,
                'score_percentage' => $scorePercentage,
                'passed' => $passed,
            ]);

            return [
                'attempt_id' => $attempt->id,
                'score_percentage' => $scorePercentage,
                'correct_answers' => $correctCount,
                'total_questions' => $attempt->total_questions,
                'passed' => $passed,
                'time_taken_seconds' => $attempt->time_taken_seconds,
            ];
        });
    }

    /**
     * Get attempt results with detailed breakdown
     */
    public function getAttemptResults(User $user, int $attemptId): array
    {
        $attempt = QuizAttempt::with(['quiz', 'subject', 'answers.question', 'answers.option'])
            ->where('user_id', $user->id)
            ->findOrFail($attemptId);

        return [
            'id' => $attempt->id,
            'quiz' => [
                'id' => $attempt->quiz->id,
                'title' => $attempt->quiz->title,
            ],
            'subject' => $attempt->subject ? [
                'id' => $attempt->subject->id,
                'name' => $attempt->subject->name,
            ] : null,
            'score_percentage' => $attempt->score_percentage,
            'correct_answers' => $attempt->correct_answers,
            'total_questions' => $attempt->total_questions,
            'passed' => $attempt->passed,
            'time_taken_seconds' => $attempt->time_taken_seconds,
            'started_at' => $attempt->started_at->toIso8601String(),
            'completed_at' => $attempt->completed_at?->toIso8601String(),
            'answers' => $attempt->answers->map(function ($answer) {
                return [
                    'question_id' => $answer->question_id,
                    'question_text' => $answer->question->question_text,
                    'selected_option_id' => $answer->option_id,
                    'selected_option_label' => $answer->option?->label,
                    'is_correct' => $answer->is_correct,
                    'explanation' => $answer->question->explanation,
                    'time_spent_seconds' => $answer->time_spent_seconds,
                ];
            })->toArray(),
        ];
    }
}
