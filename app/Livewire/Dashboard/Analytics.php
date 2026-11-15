<?php

namespace App\Livewire\Dashboard;

use App\Models\Lesson;
use App\Models\MockSession;
use App\Models\QuizAttempt;
use App\Models\Subject;
use App\Models\Topic;
use App\Models\User;
use App\Models\UserAnswer;
use App\Models\UserProgress;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Attributes\Layout;
use Livewire\Component;

class Analytics extends Component
{
    #[Layout('components.layouts.app')]
    public function render()
    {
        $authenticatedUser = Auth::user();
        $studentId = request()->integer('student');
        $user = $authenticatedUser;

        // If student parameter provided, verify parent is linked to this student
        if ($studentId > 0) {
            $student = User::find($studentId);
            $canViewStudent = $authenticatedUser->hasAnyRole(['admin', 'super-admin'])
                || $authenticatedUser->children()->where('users.id', $studentId)->exists();

            if (! $student || ! $canViewStudent) {
                abort(403, 'Unauthorized to view this student\'s analytics');
            }

            $user = $student;
        }

        // Quiz scores over time (last 30 days, or recent completed activity)
        $scoresQuery = QuizAttempt::where('user_id', $user->id)
            ->where('status', 'completed')
            ->whereNotNull('completed_at')
            ->with(['quiz', 'subject', 'mockSession.examType']);

        $recentAttempts = (clone $scoresQuery)
            ->where('completed_at', '>=', now()->subDays(30))
            ->orderBy('completed_at')
            ->get();

        $isFallbackRecent = false;
        if ($recentAttempts->isEmpty()) {
            $recentAttempts = (clone $scoresQuery)
                ->orderByDesc('completed_at')
                ->limit(30)
                ->get()
                ->reverse();
            $isFallbackRecent = $recentAttempts->isNotEmpty();
        }

        $quizScoresOverTime = $recentAttempts->map(function ($attempt) {
            $title = $attempt->quiz?->title
                ?? ($attempt->subject?->name ? $attempt->subject->name.' Practice' : null)
                ?? ($attempt->mockSession?->examType?->name ? $attempt->mockSession->examType->name.' Mock' : null)
                ?? 'Quiz';

            return [
                'date' => $attempt->completed_at ? $attempt->completed_at->format('M d') : '',
                'score' => round($attempt->score_percentage ?? 0, 1),
                'title' => $title,
            ];
        })->values();

        // Subject performance (all time) - includes quizzes, subject tests, and mock exams
        $subjectPerformance = QuizAttempt::where('user_id', $user->id)
            ->where('status', 'completed')
            ->with(['subject', 'quiz.subject', 'mockSession'])
            ->get()
            ->groupBy(function ($attempt) {
                return $attempt->subject_id
                    ?? $attempt->quiz?->subject_id
                    ?? ($attempt->mockSession?->subject_ids[0] ?? null);
            })
            ->filter(fn ($attempts, $subjectId) => ! empty($subjectId))
            ->map(function ($attempts, $subjectId) {
                $subject = $attempts->first()->subject
                    ?? $attempts->first()->quiz?->subject
                    ?? Subject::find($subjectId);

                return [
                    'subject' => $subject?->name ?? 'Subject',
                    'avg_score' => round($attempts->whereNotNull('score_percentage')->avg('score_percentage') ?? 0, 1),
                    'total_attempts' => $attempts->count(),
                ];
            })
            ->values();

        // Topic mastery: derive first from actual questions answered across attempts and quizzes
        $topicMastery = collect();

        if (Schema::hasTable('topics') && Schema::hasTable('user_answers') && Schema::hasTable('questions')) {
            $topicMastery = UserAnswer::where('user_answers.user_id', $user->id)
                ->join('questions', 'user_answers.question_id', '=', 'questions.id')
                ->join('topics', 'questions.topic_id', '=', 'topics.id')
                ->select(
                    'topics.id as topic_id',
                    'topics.name as topic_name',
                    DB::raw('COUNT(*) as total_answers'),
                    DB::raw('SUM(CASE WHEN user_answers.is_correct = 1 THEN 1 ELSE 0 END) as correct_answers')
                )
                ->groupBy('topics.id', 'topics.name')
                ->having('total_answers', '>=', 1)
                ->orderByDesc('total_answers')
                ->limit(10)
                ->get()
                ->map(function ($item) {
                    $avgScore = $item->total_answers > 0
                        ? round(($item->correct_answers / $item->total_answers) * 100, 1)
                        : 0;

                    return [
                        'topic' => $item->topic_name,
                        'avg_score' => $avgScore,
                        'total_attempts' => (int) $item->total_answers,
                        'mastery_level' => $this->getMasteryLevel($avgScore),
                    ];
                })
                ->values();

            // Fallback to quiz-level topic_ids JSON if user has not yet answered question-level topics
            if ($topicMastery->isEmpty() && Schema::hasTable('quizzes')) {
                $topicMastery = QuizAttempt::select(
                    'quizzes.topic_ids',
                    DB::raw('AVG(quiz_attempts.score_percentage) as avg_score'),
                    DB::raw('COUNT(*) as total_attempts')
                )
                    ->join('quizzes', 'quiz_attempts.quiz_id', '=', 'quizzes.id')
                    ->where('quiz_attempts.user_id', $user->id)
                    ->where('quiz_attempts.status', 'completed')
                    ->whereNotNull('quizzes.topic_ids')
                    ->groupBy('quizzes.topic_ids')
                    ->get()
                    ->map(function ($item) {
                        $topicIds = json_decode($item->topic_ids, true);
                        if (! empty($topicIds)) {
                            $topic = Topic::find($topicIds[0]);

                            return [
                                'topic' => $topic?->name ?? 'Topic',
                                'avg_score' => round($item->avg_score, 1),
                                'total_attempts' => (int) $item->total_attempts,
                                'mastery_level' => $this->getMasteryLevel($item->avg_score),
                            ];
                        }

                        return null;
                    })
                    ->filter()
                    ->values();
            }
        }

        // Study streak (last 30 days activity across quizzes, mock exams, and video progress)
        $startDate30 = now()->subDays(29)->startOfDay();

        $quizActiveDates = QuizAttempt::where('user_id', $user->id)
            ->where('created_at', '>=', $startDate30)
            ->selectRaw('DATE(created_at) as act_date')
            ->pluck('act_date')
            ->all();

        $userProgressDates = Schema::hasTable('user_progress')
            ? UserProgress::where('user_id', $user->id)
                ->where('updated_at', '>=', $startDate30)
                ->selectRaw('DATE(updated_at) as act_date')
                ->pluck('act_date')
                ->all()
            : [];

        $videoProgressDates = Schema::hasTable('video_progress')
            ? DB::table('video_progress')
                ->where('user_id', $user->id)
                ->where('updated_at', '>=', $startDate30)
                ->selectRaw('DATE(updated_at) as act_date')
                ->pluck('act_date')
                ->all()
            : [];

        $allActiveDates = array_flip(array_unique(array_merge($quizActiveDates, $userProgressDates, $videoProgressDates)));

        $studyStreak = collect();
        $currentStreak = 0;
        $longestStreak = 0;
        $tempStreak = 0;

        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $dateStr = $date->format('Y-m-d');
            $hasActivity = isset($allActiveDates[$dateStr]);

            $studyStreak->push([
                'date' => $date->format('M d'),
                'active' => $hasActivity ? 1 : 0,
            ]);

            if ($hasActivity) {
                $tempStreak++;
                if ($i === 0) {
                    $currentStreak = $tempStreak;
                }
                $longestStreak = max($longestStreak, $tempStreak);
            } else {
                if ($i === 0) {
                    $currentStreak = 0;
                }
                $tempStreak = 0;
            }
        }

        // Time spent per day (last 14 days combining quizzes and video lessons)
        $startDate14 = now()->subDays(13)->startOfDay();

        $dailyProgressTimes = Schema::hasTable('user_progress')
            ? UserProgress::where('user_id', $user->id)
                ->where('updated_at', '>=', $startDate14)
                ->selectRaw('DATE(updated_at) as act_date, SUM(time_spent_seconds) as total_seconds')
                ->groupBy('act_date')
                ->pluck('total_seconds', 'act_date')
                ->all()
            : [];

        $dailyQuizTimes = QuizAttempt::where('user_id', $user->id)
            ->where('created_at', '>=', $startDate14)
            ->selectRaw('DATE(created_at) as act_date, SUM(COALESCE(time_taken_seconds, time_spent_seconds, 0)) as total_seconds')
            ->groupBy('act_date')
            ->pluck('total_seconds', 'act_date')
            ->all();

        $timeSpentDaily = collect();
        for ($i = 13; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $dateStr = $date->format('Y-m-d');
            $seconds = (int) ($dailyProgressTimes[$dateStr] ?? 0) + (int) ($dailyQuizTimes[$dateStr] ?? 0);

            $timeSpentDaily->push([
                'date' => $date->format('M d'),
                'minutes' => round($seconds / 60, 1),
            ]);
        }

        // Overall statistics
        $totalQuizzes = QuizAttempt::where('user_id', $user->id)
            ->where('status', 'completed')
            ->count();

        $mockAttempts = QuizAttempt::where('user_id', $user->id)
            ->mockExams()
            ->where('status', 'completed');

        $mockAttemptIds = (clone $mockAttempts)->pluck('id');
        $mockAttemptsCount = $mockAttemptIds->count();

        // Also include completed MockSession records that might be unlinked to a QuizAttempt
        $unlinkedCompletedMockSessions = MockSession::where('user_id', $user->id)
            ->where('status', 'completed')
            ->where(function ($q) use ($mockAttemptIds) {
                $q->whereNull('quiz_attempt_id')
                    ->orWhereNotIn('quiz_attempt_id', $mockAttemptIds);
            })
            ->count();

        $mockExamsTaken = $mockAttemptsCount + $unlinkedCompletedMockSessions;

        $bestMockScore = (clone $mockAttempts)->max('score_percentage') ?? 0;

        $averageScore = QuizAttempt::where('user_id', $user->id)
            ->where('status', 'completed')
            ->whereNotNull('score_percentage')
            ->avg('score_percentage') ?? 0;

        $videosWatched = $user->videoProgress()
            ->where('completed', true)
            ->count();

        $totalVideos = Lesson::where('status', 'published')
            ->whereNotNull('video_url')
            ->count();

        $totalLessons = Schema::hasTable('user_progress')
            ? UserProgress::where('user_id', $user->id)
                ->where('type', 'lesson')
                ->where('is_completed', true)
                ->count()
            : 0;

        $userProgressTime = Schema::hasTable('user_progress')
            ? (int) UserProgress::where('user_id', $user->id)->sum('time_spent_seconds')
            : 0;

        $totalTimeSpent = $userProgressTime
            + (int) QuizAttempt::where('user_id', $user->id)->sum(DB::raw('COALESCE(time_taken_seconds, time_spent_seconds, 0)'));

        return view('livewire.dashboard.analytics', [
            'quizScoresOverTime' => $quizScoresOverTime,
            'isFallbackRecent' => $isFallbackRecent,
            'subjectPerformance' => $subjectPerformance,
            'topicMastery' => $topicMastery,
            'studyStreak' => $studyStreak,
            'timeSpentDaily' => $timeSpentDaily,
            'currentStreak' => $currentStreak,
            'longestStreak' => $longestStreak,
            'totalQuizzes' => $totalQuizzes,
            'mockExamsTaken' => $mockExamsTaken,
            'bestMockScore' => $bestMockScore,
            'videosWatched' => $videosWatched,
            'totalVideos' => $totalVideos,
            'averageScore' => round($averageScore, 1),
            'totalLessons' => $totalLessons,
            'totalTimeSpent' => $totalTimeSpent,
        ]);
    }

    public function toJSON(): array
    {
        return [];
    }

    private function getMasteryLevel($score)
    {
        if ($score >= 90) {
            return 'Expert';
        }
        if ($score >= 75) {
            return 'Proficient';
        }
        if ($score >= 60) {
            return 'Developing';
        }

        return 'Beginner';
    }
}
