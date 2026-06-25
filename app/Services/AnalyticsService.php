<?php

namespace App\Services;

use App\Models\QuizAttempt;
use App\Models\Subject;
use App\Models\User;

class AnalyticsService
{
    /**
     * Get user overview statistics
     */
    public function getUserOverview(User $user): array
    {
        return [
            'total_quizzes' => QuizAttempt::where('user_id', $user->id)->count(),
            'average_score' => QuizAttempt::where('user_id', $user->id)
                ->whereNotNull('score_percentage')
                ->avg('score_percentage'),
            'total_time_spent' => QuizAttempt::where('user_id', $user->id)
                ->sum('time_taken_seconds'),
            'study_streak' => $this->calculateStudyStreak($user),
        ];
    }

    /**
     * Get subject performance breakdown
     */
    public function getSubjectPerformance(User $user): array
    {
        $performance = QuizAttempt::where('user_id', $user->id)
            ->whereNotNull('subject_id')
            ->selectRaw('
                subject_id,
                COUNT(*) as total_attempts,
                AVG(score_percentage) as avg_score,
                SUM(time_taken_seconds) as total_time
            ')
            ->groupBy('subject_id')
            ->get();

        return $performance->map(function ($item) {
            $subject = Subject::find($item->subject_id);

            return [
                'subject' => $subject ? [
                    'id' => $subject->id,
                    'name' => $subject->name,
                    'code' => $subject->code,
                    'slug' => $subject->slug,
                    'icon' => $subject->icon,
                    'color' => $subject->color,
                ] : null,
                'total_attempts' => $item->total_attempts,
                'average_score' => round($item->avg_score, 2),
                'total_time_spent_seconds' => $item->total_time,
            ];
        })->toArray();
    }

    /**
     * Get recent quiz history
     */
    public function getQuizHistory(User $user, int $limit = 10): array
    {
        return QuizAttempt::where('user_id', $user->id)
            ->with(['subject', 'quiz'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($attempt) {
                return [
                    'id' => $attempt->id,
                    'subject' => $attempt->subject ? [
                        'id' => $attempt->subject->id,
                        'name' => $attempt->subject->name,
                        'code' => $attempt->subject->code,
                    ] : null,
                    'quiz' => $attempt->quiz ? [
                        'id' => $attempt->quiz->id,
                        'title' => $attempt->quiz->title,
                    ] : null,
                    'score_percentage' => $attempt->score_percentage,
                    'passed' => $attempt->passed,
                    'time_taken_seconds' => $attempt->time_taken_seconds,
                    'completed_at' => $attempt->completed_at?->toIso8601String(),
                ];
            })->toArray();
    }

    /**
     * Calculate study streak (consecutive days with activity)
     */
    public function calculateStudyStreak(User $user): int
    {
        $lastActivityDate = QuizAttempt::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->value('created_at');

        if (! $lastActivityDate) {
            return 0;
        }

        $streak = 0;
        $currentDate = now()->startOfDay();

        while ($currentDate->gte($lastActivityDate->startOfDay())) {
            $hasActivity = QuizAttempt::where('user_id', $user->id)
                ->whereDate('created_at', $currentDate)
                ->exists();

            if ($hasActivity) {
                $streak++;
                $currentDate->subDay();
            } else {
                break;
            }
        }

        return $streak;
    }
}
