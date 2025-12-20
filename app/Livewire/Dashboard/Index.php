<?php

namespace App\Livewire\Dashboard;

use App\Enums\QuizType;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Subject;
use App\Models\Video;
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

    public function mount()
    {
        $user = auth()->user();

        // Get enrolled subjects
        $this->enrolledSubjects = $user->enrolledSubjects()
            ->with('examTypes')
            ->get();

        // Calculate stats
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
        ];

        // Get recent videos from enrolled subjects
        $subjectIds = $this->enrolledSubjects->pluck('id')->toArray();
        $this->recentVideos = Video::whereIn('subject_id', $subjectIds)
            ->where('is_published', true)
            ->with(['subject', 'topic'])
            ->latest()
            ->take(6)
            ->get();

        // Get recent quizzes
        // Get recent quizzes
        $this->recentQuizzes = Quiz::where('status', 'published')
            ->with('subject')
            ->latest('published_at')
            ->take(6)
            ->get();

        // Get available mock exam quizzes (JAMB format)
        $this->mockExamQuizzes = Quiz::where('status', 'published')
            ->where('type', QuizType::Mock->value)
            ->with(['subject'])
            ->latest('published_at')
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
        return view('livewire.dashboard.index');
    }
}
