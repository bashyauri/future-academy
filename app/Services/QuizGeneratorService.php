<?php

namespace App\Services;

use App\Models\Quiz;
use App\Models\Question;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Support\Collection;

class QuizGeneratorService
{
    /**
     * Generate a new quiz attempt for a user.
     */
    public function generateAttempt(Quiz $quiz, User $user): QuizAttempt
    {
        // Get questions based on quiz criteria
        $questions = $this->selectQuestions($quiz);

        // Shuffle if enabled
        if ($quiz->shuffle_questions) {
            $questions = $questions->shuffle();
        }

        // Create attempt
        $attempt = QuizAttempt::create([
            'quiz_id' => $quiz->id,
            'user_id' => $user->id,
            'attempt_number' => $quiz->getNextAttemptNumber($user),
            'started_at' => now(),
            'status' => 'in_progress',
            'question_order' => $questions->pluck('id')->toArray(),
        ]);

        return $attempt;
    }

    /**
     * Select questions based on quiz criteria.
     */
    protected function selectQuestions(Quiz $quiz): Collection
    {
        // If quiz already has manually assigned questions, use those
        if ($quiz->questions()->exists()) {
            return $quiz->questions;
        }

        // Build query based on criteria
        $query = Question::query()
            ->approved()
            ->active()
            ->with(['options', 'subject', 'topic', 'examType']);

        // Apply filters
        if ($quiz->subject_ids) {
            $query->whereIn('subject_id', $quiz->subject_ids);
        }

        if ($quiz->topic_ids) {
            $query->whereIn('topic_id', $quiz->topic_ids);
        }

        if ($quiz->exam_type_ids) {
            $query->whereIn('exam_type_id', $quiz->exam_type_ids);
        }

        if ($quiz->difficulty_levels) {
            $query->whereIn('difficulty', $quiz->difficulty_levels);
        }

        if ($quiz->years) {
            $query->whereIn('year', $quiz->years);
        }

        // Get all matching questions
        $allQuestions = $query->get();

        // Randomize selection if enabled
        if ($quiz->randomize_questions) {
            $allQuestions = $allQuestions->shuffle();
        }

        // Take the specified number of questions
        return $allQuestions->take($quiz->question_count);
    }

    /**
     * Get shuffled options for a question if shuffle is enabled.
     */
    public function getShuffledOptions(Quiz $quiz, Question $question): Collection
    {
        $options = $question->options;

        if ($quiz->shuffle_options) {
            return $options->shuffle();
        }

        return $options;
    }

    /**
     * Check if an attempt should be auto-submitted due to timeout.
     */
    public function checkTimeout(QuizAttempt $attempt): bool
    {
        if ($attempt->hasTimedOut()) {
            $attempt->update([
                'status' => 'timed_out',
                'completed_at' => now(),
                'time_spent_seconds' => $attempt->quiz->duration_minutes * 60,
            ]);
            $attempt->calculateScore();
            return true;
        }

        return false;
    }

    /**
     * Submit an answer for a question in an attempt.
     */
    public function submitAnswer(QuizAttempt $attempt, int $questionId, ?int $optionId): void
    {
        $question = Question::with('options')->findOrFail($questionId);

        $isCorrect = false;
        if ($optionId) {
            $selectedOption = $question->options()->find($optionId);
            $isCorrect = $selectedOption?->is_correct ?? false;
        }

        $attempt->answers()->updateOrCreate(
            [
                'question_id' => $questionId,
            ],
            [
                'option_id' => $optionId,
                'is_correct' => $isCorrect,
            ]
        );
    }

    /**
     * Complete a quiz attempt.
     */
    public function completeAttempt(QuizAttempt $attempt): void
    {
        $attempt->complete();
    }

    /**
     * Get quiz statistics for a user.
     */
    public function getUserStats(Quiz $quiz, User $user): array
    {
        $attempts = $quiz->attempts()
            ->where('user_id', $user->id)
            ->completed()
            ->get();

        return [
            'total_attempts' => $attempts->count(),
            'best_score' => $attempts->max('score_percentage'),
            'average_score' => $attempts->avg('score_percentage'),
            'passed_count' => $attempts->where('passed', true)->count(),
            'last_attempt' => $attempts->sortByDesc('completed_at')->first(),
        ];
    }
}
