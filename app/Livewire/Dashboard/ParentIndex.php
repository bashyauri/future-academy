<?php

namespace App\Livewire\Dashboard;

use App\Enums\QuizType;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Subject;
use App\Models\Video;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class ParentIndex extends Component
{
    public $stats = [];
    public $children = [];
    public $childrenProgress = [];
    public $childrenStats = [];
    public $subscriptions = [];
    public $studentEmail = '';
    public $linkSuccessMessage = '';

    public function mount()
    {
        $this->refreshDashboardData();
    }

    public function linkStudent()
    {
        $this->resetErrorBag('studentEmail');
        $this->linkSuccessMessage = '';

        $this->validate([
            'studentEmail' => ['required', 'email'],
        ]);

        $parent = auth()->user();
        $student = User::where('email', $this->studentEmail)->first();

        if (!$student || !$student->isStudent()) {
            $this->addError('studentEmail', __('Student not found.'));
            return;
        }

        if ($student->id === $parent->id) {
            $this->addError('studentEmail', __('You cannot link your own account.'));
            return;
        }

        $alreadyLinked = $parent->children()->where('users.id', $student->id)->exists();

        if ($alreadyLinked) {
            $parent->children()->updateExistingPivot($student->id, [
                'is_active' => true,
                'linked_at' => now(),
            ]);
        } else {
            $parent->children()->syncWithoutDetaching([
                $student->id => [
                    'is_active' => true,
                    'linked_at' => now(),
                ],
            ]);
        }

        $this->studentEmail = '';
        $this->linkSuccessMessage = __('Student linked successfully.');
        $this->refreshDashboardData();
    }

    private function refreshDashboardData()
    {
        $parent = auth()->user();

        // Get linked children (students)
        $this->children = $parent->children()
            ->with(['enrolledSubjects', 'subscriptions'])
            ->get();

        // Get parent's subscription(s)
        $this->subscriptions = $parent->subscriptions()
            ->where('status', 'active')
            ->orWhere('status', 'pending')
            ->latest('created_at')
            ->get();

        // Calculate combined stats from all children
        $this->calculateCombinedStats();

        // Get individual progress for each child
        $this->calculateChildrenProgress();
    }

    private function calculateCombinedStats()
    {
        $totalVideosWatched = 0;
        $totalTotalVideos = 0;
        $totalQuizzesTaken = 0;
        $totalTotalQuizzes = 0;
        $averageScore = 0;
        $totalMockExamsTaken = 0;
        $bestMockScore = 0;
        $totalSubjectsEnrolled = 0;

        foreach ($this->children as $child) {
            $videosWatched = $child->videoProgress()->where('completed', true)->count();
            $totalVideos = Video::where('is_published', true)->count();
            $quizzesTaken = $child->quizAttempts()->where('status', 'completed')->count();
            $totalQuizzes = Quiz::where('status', 'published')->count();
            $childAvgScore = $child->quizAttempts()
                ->where('status', 'completed')
                ->avg('score_percentage') ?? 0;
            $mockExamsTaken = $child->quizAttempts()
                ->whereHas('quiz', function ($query) {
                    $query->where('type', QuizType::Mock->value);
                })
                ->where('status', 'completed')
                ->count();
            $bestMock = $child->quizAttempts()
                ->whereHas('quiz', function ($query) {
                    $query->where('type', QuizType::Mock->value);
                })
                ->where('status', 'completed')
                ->max('score_percentage') ?? 0;
            $subjectsEnrolled = $child->enrolledSubjects()->count();

            $totalVideosWatched += $videosWatched;
            $totalTotalVideos += $totalVideos;
            $totalQuizzesTaken += $quizzesTaken;
            $totalTotalQuizzes += $totalQuizzes;
            $averageScore += $childAvgScore;
            $totalMockExamsTaken += $mockExamsTaken;
            if ($bestMock > $bestMockScore) {
                $bestMockScore = $bestMock;
            }
            $totalSubjectsEnrolled += $subjectsEnrolled;
        }

        $childCount = count($this->children);
        $this->stats = [
            'videos_watched' => $totalVideosWatched,
            'total_videos' => $totalTotalVideos,
            'quizzes_taken' => $totalQuizzesTaken,
            'total_quizzes' => $totalTotalQuizzes,
            'average_score' => $childCount > 0 ? $averageScore / $childCount : 0,
            'subjects_enrolled' => $totalSubjectsEnrolled,
            'mock_exams_taken' => $totalMockExamsTaken,
            'best_mock_score' => $bestMockScore,
            'children_count' => $childCount,
        ];
    }

    private function calculateChildrenProgress()
    {
        foreach ($this->children as $child) {
            $videosWatched = $child->videoProgress()->where('completed', true)->count();
            $totalVideos = Video::where('is_published', true)->count();
            $avgScore = $child->quizAttempts()
                ->where('status', 'completed')
                ->avg('score_percentage') ?? 0;
            $mockExamsTaken = $child->quizAttempts()
                ->whereHas('quiz', function ($query) {
                    $query->where('type', QuizType::Mock->value);
                })
                ->where('status', 'completed')
                ->count();
            $bestMockScore = $child->quizAttempts()
                ->whereHas('quiz', function ($query) {
                    $query->where('type', QuizType::Mock->value);
                })
                ->where('status', 'completed')
                ->max('score_percentage') ?? 0;
            $subjectsEnrolled = $child->enrolledSubjects()->count();

            $this->childrenStats[$child->id] = [
                'videos_watched' => $videosWatched,
                'total_videos' => $totalVideos,
                'videos_percentage' => $totalVideos > 0 ? round(($videosWatched / $totalVideos) * 100) : 0,
                'average_score' => number_format($avgScore, 1),
                'mock_exams_taken' => $mockExamsTaken,
                'best_mock_score' => $bestMockScore,
                'subjects_enrolled' => $subjectsEnrolled,
            ];
        }
    }

    public function render()
    {
        return view('livewire.dashboard.parent-index');
    }
}
