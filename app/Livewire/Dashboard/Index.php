<?php

namespace App\Livewire\Dashboard;

use App\Enums\QuizType;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Subject;
use App\Models\Video;
use App\Models\VideoAnalytics;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class Index extends Component
{
    public $stats = [];
    public $recentVideos = [];
    public $recentQuizzes = [];
    public $mockExamQuizzes = [];
    public $recentMockAttempts = [];
    public $enrolledSubjects = [];
    public $subscriptions = [];
    public function mount()
    {
        $user = auth()->user();
        // Get payment/subscription history
        $this->subscriptions = $user->subscriptions()->latest('created_at')->get();

        // Get enrolled subjects
        $this->enrolledSubjects = $user->enrolledSubjects()
            ->with('examTypes')
            ->get();

        // Calculate stats
        $subjectIds = $this->enrolledSubjects->pluck('id')->toArray();

        // Get video analytics for enrolled subjects
        // Extract lesson IDs from enrolled subjects (reload with lessons to avoid N+1)
        $lessonIds = $user->enrolledSubjects()
            ->with('lessons')
            ->get()
            ->flatMap(function ($subject) {
                return $subject->lessons->pluck('id');
            })
            ->unique()
            ->toArray();

        $videoAnalytics = VideoAnalytics::whereIn('lesson_id', $lessonIds)->get();

        $totalVideoViews = $videoAnalytics->sum('total_views') ?? 0;
        $totalVideoWatchTime = $videoAnalytics->sum('total_watch_time') ?? 0;
        $averageCompletionRate = $videoAnalytics->avg('completion_rate') ?? 0;

        $this->stats = [
            'videos_watched' => $user->videoProgress()->where('completed', true)->count(),
            'total_videos' => Video::where('is_published', true)->count(),
            'quizzes_taken' => $user->quizAttempts()->where('status', 'completed')->count(),
            'total_quizzes' => Quiz::where('status', 'published')->count(),
            'average_score' => $user->quizAttempts()
                ->where('status', 'completed')
                ->avg('score_percentage') ?? 0,
            'subjects_enrolled' => $this->enrolledSubjects->count(),
            'mock_exams_taken' => $user->quizAttempts()
                ->whereHas('quiz', function ($query) {
                    $query->where('type', QuizType::Mock->value);
                })
                ->where('status', 'completed')
                ->count(),
            'best_mock_score' => $user->quizAttempts()
                ->whereHas('quiz', function ($query) {
                    $query->where('type', QuizType::Mock->value);
                })
                ->where('status', 'completed')
                ->max('score_percentage') ?? 0,
            // Video analytics from Bunny Stream
            'total_video_views' => $totalVideoViews,
            'total_video_watch_time_seconds' => $totalVideoWatchTime,
            'total_video_watch_time_hours' => round($totalVideoWatchTime / 3600, 1),
            'average_completion_rate' => number_format($averageCompletionRate, 1),
        ];
        $this->recentVideos = Video::whereIn('subject_id', $subjectIds)
            ->where('is_published', true)
            ->with(['subject', 'topic'])
            ->latest()
            ->take(6)
            ->get();

        // Get recent quizzes filtered by user's exam types
        $quizQuery = Quiz::where('status', 'published')
            ->with('subject')
            ->whereHas('subject', function ($query) {
                $query->where('is_active', true);
            })
            ->latest('published_at');

        // Filter by exam types if user has selected any
        if (!empty($user->exam_types) && is_array($user->exam_types)) {
            $quizQuery->where(function ($q) use ($user) {
                foreach ($user->exam_types as $examType) {
                    $q->orWhereJsonContains('exam_type_ids', (int)$examType);
                }
            });
        }

        $this->recentQuizzes = $quizQuery->take(6)->get();

        // Get available mock exam quizzes (JAMB format)
        $mockExamQuery = Quiz::where('status', 'published')
            ->where('type', QuizType::Mock->value)
            ->with(['subject'])
            ->whereHas('subject', function ($query) {
                $query->where('is_active', true);
            })
            ->latest('published_at');

        // Apply same exam type filtering
        if (!empty($user->exam_types) && is_array($user->exam_types)) {
            $mockExamQuery->where(function ($q) use ($user) {
                foreach ($user->exam_types as $examType) {
                    $q->orWhereJsonContains('exam_type_ids', (int)$examType);
                }
            });
        }

        $this->mockExamQuizzes = $mockExamQuery
            ->take(4)
            ->get();

        // Get recent mock exam attempts
        $this->recentMockAttempts = $user->quizAttempts()
            ->whereHas('quiz', function ($query) {
                $query->where('type', QuizType::Mock->value);
            })
            ->with(['quiz.subject'])
            ->where('status', 'completed')
            ->latest('completed_at')
            ->take(5)
            ->get();
    }

    public function render()
    {
        return view('livewire.dashboard.index', [
            'subscriptions' => $this->subscriptions,
        ]);
    }
}
