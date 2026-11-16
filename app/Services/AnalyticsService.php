<?php

namespace App\Services;

use App\Models\Lesson;
use App\Models\MockSession;
use App\Models\QuizAttempt;
use App\Models\Subject;
use App\Models\User;
use App\Models\UserProgress;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AnalyticsService
{
    /**
     * Get user overview statistics
     */
    public function getUserOverview(User $user): array
    {
        $avgScore = QuizAttempt::where('user_id', $user->id)
            ->where('status', 'completed')
            ->whereNotNull('score_percentage')
            ->avg('score_percentage');

        $mockAttempts = QuizAttempt::where('user_id', $user->id)
            ->mockExams()
            ->where('status', 'completed');
        $mockAttemptIds = (clone $mockAttempts)->pluck('id');
        $unlinkedMockSessions = MockSession::where('user_id', $user->id)
            ->where('status', 'completed')
            ->where(function ($q) use ($mockAttemptIds) {
                $q->whereNull('quiz_attempt_id')
                    ->orWhereNotIn('quiz_attempt_id', $mockAttemptIds);
            })
            ->count();
        $mockExamsTaken = $mockAttemptIds->count() + $unlinkedMockSessions;
        $bestMockScore = (clone $mockAttempts)->max('score_percentage') ?? 0;

        $userProgressTime = Schema::hasTable('user_progress')
            ? (int) UserProgress::where('user_id', $user->id)->sum('time_spent_seconds')
            : 0;

        $quizTime = (int) QuizAttempt::where('user_id', $user->id)
            ->where('status', 'completed')
            ->sum(DB::raw('COALESCE(time_taken_seconds, time_spent_seconds, 0)'));

        return [
            'total_quizzes' => QuizAttempt::where('user_id', $user->id)
                ->where('status', 'completed')
                ->count(),
            'average_score' => $avgScore !== null ? round((float) $avgScore, 1) : null,
            'total_time_spent' => $quizTime + $userProgressTime,
            'study_streak' => $this->calculateStudyStreak($user),
            'mock_exams_taken' => $mockExamsTaken,
            'best_mock_score' => round((float) $bestMockScore, 1),
            'videos_watched' => $user->videoProgress()->where('completed', true)->count(),
            'total_videos' => Lesson::where('status', 'published')
                ->whereNotNull('video_url')
                ->count(),
        ];
    }

    /**
     * Get subject performance breakdown
     */
    public function getSubjectPerformance(User $user): array
    {
        $attempts = QuizAttempt::where('user_id', $user->id)
            ->where('status', 'completed')
            ->with(['subject', 'quiz.subject', 'mockSession'])
            ->get();

        $grouped = $attempts->groupBy(function ($attempt) {
            return $attempt->subject_id
                ?? $attempt->quiz?->subject_id
                ?? ($attempt->mockSession?->subject_ids[0] ?? null);
        })->filter(fn ($group, $subjectId) => ! empty($subjectId));

        return $grouped->map(function ($group, $subjectId) {
            $subject = $group->first()->subject
                ?? $group->first()->quiz?->subject
                ?? Subject::find($subjectId);

            $scores = $group->whereNotNull('score_percentage');

            return [
                'subject_id' => (int) $subjectId,
                'subject_name' => $subject?->name ?? 'Subject',
                'subject_code' => $subject?->code ?? '',
                'total_attempts' => $group->count(),
                'average_score' => round((float) ($scores->avg('score_percentage') ?? 0), 2),
                'best_score' => round((float) ($scores->max('score_percentage') ?? 0), 2),
                'total_time_spent_seconds' => (int) $group->sum(fn ($a) => $a->time_taken_seconds ?? $a->time_spent_seconds ?? 0),
            ];
        })->values()->toArray();
    }

    /**
     * Get recent quiz history
     */
    public function getQuizHistory(User $user, int $limit = 10): array
    {
        return QuizAttempt::where('user_id', $user->id)
            ->where('status', 'completed')
            ->with(['subject', 'quiz.subject', 'mockSession.examType'])
            ->latest('completed_at')
            ->limit($limit)
            ->get()
            ->map(function ($attempt) {
                $subjectName = $attempt->subject?->name
                    ?? $attempt->quiz?->subject?->name
                    ?? ($attempt->mockSession?->examType?->name ? $attempt->mockSession->examType->name.' Mock' : 'General');

                $quizType = $attempt->quiz?->title
                    ?? ($attempt->mockSession?->examType?->name ? $attempt->mockSession->examType->name.' Mock Exam' : ($attempt->subject?->name ? $attempt->subject->name.' Practice' : 'Quiz Attempt'));

                return [
                    'id' => $attempt->id,
                    'quiz_type' => $quizType,
                    'subject_name' => $subjectName,
                    'score' => $attempt->score_percentage,
                    'total_questions' => $attempt->total_questions ?? 0,
                    'completed_at' => $attempt->completed_at?->toIso8601String(),
                ];
            })
            ->toArray();
    }

    /**
     * Calculate study streak (consecutive days with activity across quizzes, mocks and videos)
     */
    public function calculateStudyStreak(User $user): int
    {
        return $this->getStudyStreakDetail($user)['current_streak'];
    }

    /**
     * Get detailed study streak data including longest streak and active days.
     *
     * @return array{current_streak: int, longest_streak: int, last_activity_date: string|null, active_days: string[]}
     */
    public function getStudyStreakDetail(User $user): array
    {
        $quizDates = QuizAttempt::where('user_id', $user->id)
            ->selectRaw('DATE(created_at) as act_date')
            ->pluck('act_date')
            ->all();

        $progressDates = Schema::hasTable('user_progress')
            ? UserProgress::where('user_id', $user->id)
                ->selectRaw('DATE(updated_at) as act_date')
                ->pluck('act_date')
                ->all()
            : [];

        $videoDates = Schema::hasTable('video_progress')
            ? DB::table('video_progress')
                ->where('user_id', $user->id)
                ->selectRaw('DATE(updated_at) as act_date')
                ->pluck('act_date')
                ->all()
            : [];

        $uniqueDates = array_values(array_unique(array_merge($quizDates, $progressDates, $videoDates)));
        sort($uniqueDates);

        $activeDaySet = array_flip($uniqueDates);

        // Last 30 days for the calendar
        $calendarDays = [];
        $today = now()->startOfDay();
        for ($i = 29; $i >= 0; $i--) {
            $d = (clone $today)->subDays($i)->format('Y-m-d');
            if (isset($activeDaySet[$d])) {
                $calendarDays[] = $d;
            }
        }

        if (empty($uniqueDates)) {
            return [
                'current_streak' => 0,
                'longest_streak' => 0,
                'last_activity_date' => null,
                'active_days' => [],
            ];
        }

        // Current streak (walk backwards from today)
        $currentStreak = 0;
        $checkDate = now()->startOfDay();
        if (! isset($activeDaySet[$checkDate->format('Y-m-d')])) {
            $checkDate->subDay();
        }
        while (isset($activeDaySet[$checkDate->format('Y-m-d')])) {
            $currentStreak++;
            $checkDate->subDay();
        }

        // Longest streak (scan all dates)
        $longestStreak = 0;
        $run = 1;
        for ($i = 1; $i < count($uniqueDates); $i++) {
            $prev = new \DateTime($uniqueDates[$i - 1]);
            $curr = new \DateTime($uniqueDates[$i]);
            $diff = (int) $prev->diff($curr)->days;
            if ($diff === 1) {
                $run++;
                $longestStreak = max($longestStreak, $run);
            } else {
                $run = 1;
            }
        }
        $longestStreak = max($longestStreak, $currentStreak, count($uniqueDates) > 0 ? 1 : 0);

        return [
            'current_streak' => $currentStreak,
            'longest_streak' => $longestStreak,
            'last_activity_date' => end($uniqueDates) ?: null,
            'active_days' => $calendarDays,
        ];
    }
}
