<?php

namespace App\Livewire\Dashboard;

use App\Models\Lesson;
use App\Models\QuizAttempt;
use App\Models\Subject;
use App\Models\Topic;
use App\Models\User;
use App\Models\UserProgress;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

        // Quiz scores over time (last 30 days)
        $quizScoresOverTime = QuizAttempt::where('user_id', $user->id)
            ->where('status', 'completed')
            ->where('completed_at', '>=', now()->subDays(30))
            ->with(['quiz', 'subject', 'mockSession.examType'])
            ->orderBy('completed_at')
            ->get()
            ->map(function ($attempt) {
                return [
                    'date' => $attempt->completed_at ? $attempt->completed_at->format('M d') : '',
                    'score' => round($attempt->score_percentage ?? 0, 1),
                    'title' => $attempt->title ?? $attempt->quiz?->title ?? 'Quiz',
                ];
            });

        // Subject performance (all time) - includes both quizzes and mock exams
        $subjectPerformance = QuizAttempt::where('user_id', $user->id)
            ->where('status', 'completed')
            ->with(['subject', 'quiz.subject'])
            ->get()
            ->groupBy(function ($attempt) {
                return $attempt->subject_id ?? $attempt->quiz?->subject_id;
            })
            ->filter(fn ($attempts, $subjectId) => ! empty($subjectId))
            ->map(function ($attempts, $subjectId) {
                $subject = $attempts->first()->subject ?? $attempts->first()->quiz?->subject ?? Subject::find($subjectId);

                return [
                    'subject' => $subject?->name ?? 'Unknown',
                    'avg_score' => round($attempts->whereNotNull('score_percentage')->avg('score_percentage') ?? 0, 1),
                    'total_attempts' => $attempts->count(),
                ];
            })
            ->values();

        // Topic mastery (topics with attempts) - using JSON column
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
                        'topic' => $topic?->name ?? 'Unknown',
                        'avg_score' => round($item->avg_score, 1),
                        'total_attempts' => $item->total_attempts,
                        'mastery_level' => $this->getMasteryLevel($item->avg_score),
                    ];
                }

                return null;
            })
            ->filter()
            ->values();

        // Study streak (last 30 days activity)
        $studyStreak = collect();
        $currentStreak = 0;
        $longestStreak = 0;
        $tempStreak = 0;

        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $hasActivity = UserProgress::where('user_id', $user->id)
                ->whereDate('updated_at', $date->format('Y-m-d'))
                ->exists();

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

        // Time spent per day (last 14 days)
        $timeSpentDaily = collect();
        for ($i = 13; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $timeSpent = UserProgress::where('user_id', $user->id)
                ->whereDate('updated_at', $date->format('Y-m-d'))
                ->sum('time_spent_seconds');

            $timeSpentDaily->push([
                'date' => $date->format('M d'),
                'minutes' => round($timeSpent / 60, 1),
            ]);
        }

        // Overall statistics
        $totalQuizzes = QuizAttempt::where('user_id', $user->id)
            ->where('status', 'completed')
            ->count();

        $mockExamsTaken = QuizAttempt::where('user_id', $user->id)
            ->mockExams()
            ->where('status', 'completed')
            ->count();

        $bestMockScore = QuizAttempt::where('user_id', $user->id)
            ->mockExams()
            ->where('status', 'completed')
            ->max('score_percentage') ?? 0;

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

        $totalLessons = UserProgress::where('user_id', $user->id)
            ->where('type', 'lesson')
            ->where('is_completed', true)
            ->count();

        $totalTimeSpent = UserProgress::where('user_id', $user->id)
            ->sum('time_spent_seconds');

        return view('livewire.dashboard.analytics', [
            'quizScoresOverTime' => $quizScoresOverTime,
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
