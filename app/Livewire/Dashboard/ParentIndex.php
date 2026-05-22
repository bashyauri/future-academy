<?php

namespace App\Livewire\Dashboard;

use App\Enums\QuizType;
use App\Models\Quiz;
use App\Models\User;
use App\Models\Video;
use App\Models\VideoAnalytics;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

#[Layout('components.layouts.app')]
class ParentIndex extends Component
{
    public $stats = [];

    public Collection $children;

    public $childrenProgress = [];

    public $childrenStats = [];

    public Collection $subscriptions;

    public $studentEmail = '';

    public $newStudentName = '';

    public $newStudentEmail = '';

    public $linkSuccessMessage = '';

    public $createStudentSuccessMessage = '';

    public Collection $studentSubscriptions;

    public Collection $paidStudentIds;

    public Collection $unassignedSubscriptions;

    public bool $showProgressMetrics = false;

    public function mount()
    {
        $this->refreshDashboardData();
    }

    /**
     * Public refresh method - can be called by Livewire listeners
     */
    public function refresh()
    {
        $this->refreshDashboardData();
    }

    /**
     * Listen for student enrollment changes and auto-refresh dashboard
     */
    #[On('student-enrollment-changed')]
    public function handleStudentEnrollmentChanged($studentId)
    {
        // Check if this parent is linked to the student who changed enrollments
        $parent = auth()->user();
        if ($parent->children()->where('users.id', $studentId)->exists()) {
            $this->refresh();
        }
    }

    public function linkStudent()
    {
        $this->resetErrorBag('studentEmail');
        $this->linkSuccessMessage = '';
        $this->createStudentSuccessMessage = '';

        $this->validate([
            'studentEmail' => ['required', 'email'],
        ]);

        $parent = auth()->user();
        $student = User::where('email', $this->studentEmail)->first();

        if (! $student || ! $student->isStudent()) {
            $this->addError('studentEmail', __('Student not found.'));

            return;
        }

        if ($student->id === $parent->id) {
            $this->addError('studentEmail', __('You cannot link your own account.'));

            return;
        }

        $alreadyLinked = $parent->children()->where('users.id', $student->id)->exists();

        if ($alreadyLinked) {
            $this->attachStudentToParent($parent, $student, true);
        } else {
            $this->attachStudentToParent($parent, $student);
        }

        $this->studentEmail = '';
        $this->linkSuccessMessage = __('Student linked successfully.');
        $this->refreshDashboardData();
    }

    public function createStudent(): void
    {
        $this->resetErrorBag(['newStudentName', 'newStudentEmail']);
        $this->linkSuccessMessage = '';
        $this->createStudentSuccessMessage = '';

        $this->validate([
            'newStudentName' => ['required', 'string', 'max:255'],
            'newStudentEmail' => ['required', 'email', 'max:255', 'unique:users,email'],
        ]);

        $parent = auth()->user();
        $temporaryPassword = Str::password(12);

        $student = User::create([
            'name' => $this->newStudentName,
            'email' => $this->newStudentEmail,
            'password' => Hash::make($temporaryPassword),
            'account_type' => 'student',
            'email_verified_at' => now(),
            'has_completed_onboarding' => false,
        ]);

        $this->attachStudentToParent($parent, $student);

        $this->newStudentName = '';
        $this->newStudentEmail = '';
        $this->createStudentSuccessMessage = __('Student account created and linked. Temporary password: :password', ['password' => $temporaryPassword]);

        $this->refreshDashboardData();
    }

    private function attachStudentToParent(User $parent, User $student, bool $alreadyLinked = false): void
    {
        if ($alreadyLinked) {
            $parent->children()->updateExistingPivot($student->id, [
                'is_active' => true,
                'linked_at' => now(),
            ]);

            return;
        }

        $parent->children()->syncWithoutDetaching([
            $student->id => [
                'is_active' => true,
                'linked_at' => now(),
            ],
        ]);
    }

    private function refreshDashboardData()
    {
        $parent = auth()->user();
        $this->showProgressMetrics = $this->canViewProgressMetrics($parent);

        // Get linked children (students)
        $this->children = $parent->children()
            ->with(['enrolledSubjects', 'subscriptions'])
            ->get();

        // Get parent's subscriptions for each student
        $this->subscriptions = $parent->subscriptions()
            ->whereIn('status', ['active', 'pending'])
            ->with('student')
            ->latest('created_at')
            ->get();

        // Map subscriptions by student_id for quick lookup
        $this->studentSubscriptions = $this->subscriptions
            ->whereNotNull('student_id')
            ->keyBy('student_id');

        // Keep legacy subscriptions without student mapping visible for admin cleanup.
        $this->unassignedSubscriptions = $this->subscriptions
            ->whereNull('student_id')
            ->values();

        // Strict per-student subscription: do not map global subscriptions to any student.

        $this->paidStudentIds = $this->studentSubscriptions->keys()->filter()->values();

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
        $totalLessonsCompleted = 0;
        $totalLessonsStarted = 0;
        $totalTimeSpent = 0;
        $totalVideoViews = 0;
        $totalVideoWatchTime = 0;
        $averageCompletionRate = 0;

        foreach ($this->children as $child) {
            if (! $this->showProgressMetrics && ! $this->paidStudentIds->contains($child->id)) {
                continue;
            }
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
            $hasActiveSubscription = $child->hasActiveSubscription();

            // Lesson progress tracking
            $lessonsCompleted = $child->progress()
                ->where('type', 'lesson')
                ->where('is_completed', 1)
                ->count();
            $lessonsStarted = $child->progress()
                ->where('type', 'lesson')
                ->count();
            $timeSpent = $child->progress()
                ->where('type', 'lesson')
                ->sum('time_spent_seconds') ?? 0;

            // Video analytics from Bunny Stream
            $childLessonIds = $child->enrolledSubjects()
                ->with('lessons')
                ->get()
                ->flatMap(function ($subject) {
                    return $subject->lessons->pluck('id');
                })
                ->unique()
                ->toArray();

            $childVideoAnalytics = VideoAnalytics::whereIn('lesson_id', $childLessonIds)->get();

            $childVideoViews = $childVideoAnalytics->sum('total_views') ?? 0;
            $childVideoWatchTime = $childVideoAnalytics->sum('total_watch_time') ?? 0;
            $childCompletionRate = $childVideoAnalytics->avg('completion_rate') ?? 0;

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
            $totalLessonsCompleted += $lessonsCompleted;
            $totalLessonsStarted += $lessonsStarted;
            $totalTimeSpent += $timeSpent;
            $totalVideoViews += $childVideoViews;
            $totalVideoWatchTime += $childVideoWatchTime;
            $averageCompletionRate += $childCompletionRate;
        }

        $childCount = count($this->children);
        $paidCount = $this->paidStudentIds->count();
        $this->stats = [
            'videos_watched' => $totalVideosWatched,
            'total_videos' => $totalTotalVideos,
            'quizzes_taken' => $totalQuizzesTaken,
            'total_quizzes' => $totalTotalQuizzes,
            'average_score' => $paidCount > 0 ? $averageScore / $paidCount : 0,
            'subjects_enrolled' => $totalSubjectsEnrolled,
            'mock_exams_taken' => $totalMockExamsTaken,
            'best_mock_score' => $bestMockScore,
            'lessons_completed' => $totalLessonsCompleted,
            'lessons_started' => $totalLessonsStarted,
            'lessons_percentage' => $totalLessonsStarted > 0 ? round(($totalLessonsCompleted / $totalLessonsStarted) * 100) : 0,
            'time_spent_seconds' => $totalTimeSpent,
            'time_spent_hours' => round($totalTimeSpent / 3600, 1),
            'children_count' => $childCount,
            // Video analytics from Bunny Stream
            'total_video_views' => $totalVideoViews,
            'total_video_watch_time_seconds' => $totalVideoWatchTime,
            'total_video_watch_time_hours' => round($totalVideoWatchTime / 3600, 1),
            'average_completion_rate' => $paidCount > 0 ? round($averageCompletionRate / $paidCount, 1) : 0,
        ];
    }

    private function calculateChildrenProgress()
    {
        $parent = auth()->user();

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
            // Use fresh query to ensure we get the actual count from DB with current is_active status
            $subjectsEnrolled = $child->enrolledSubjects()->count();
            $hasActiveSubscription = $child->hasActiveSubscription();

            // Lesson progress metrics
            $lessonsCompleted = $child->progress()
                ->where('type', 'lesson')
                ->where('is_completed', 1)
                ->count();
            $lessonsStarted = $child->progress()
                ->where('type', 'lesson')
                ->count();
            $lessonsPercentage = $lessonsStarted > 0 ? round(($lessonsCompleted / $lessonsStarted) * 100) : 0;
            $timeSpent = $child->progress()
                ->where('type', 'lesson')
                ->sum('time_spent_seconds') ?? 0;
            $timeSpentFormatted = $this->formatSeconds($timeSpent);

            // Video analytics from Bunny Stream
            $childLessonIds = $child->enrolledSubjects()
                ->with('lessons')
                ->get()
                ->flatMap(function ($subject) {
                    return $subject->lessons->pluck('id');
                })
                ->unique()
                ->toArray();

            $childVideoAnalytics = VideoAnalytics::whereIn('lesson_id', $childLessonIds)->get();

            $childVideoViews = $childVideoAnalytics->sum('total_views') ?? 0;
            $childVideoWatchTime = $childVideoAnalytics->sum('total_watch_time') ?? 0;
            $childCompletionRate = $childVideoAnalytics->avg('completion_rate') ?? 0;
            $childVideoWatchTimeFormatted = $this->formatSeconds($childVideoWatchTime);

            // Check if parent has paid for this specific student
            $parentPaidForStudent = $this->studentSubscriptions->has($child->id);
            $canViewMetrics = $this->showProgressMetrics || $parentPaidForStudent;

            $this->childrenStats[$child->id] = [
                'videos_watched' => $videosWatched,
                'total_videos' => $totalVideos,
                'videos_percentage' => $totalVideos > 0 ? round(($videosWatched / $totalVideos) * 100) : 0,
                'average_score' => number_format($avgScore, 1),
                'mock_exams_taken' => $mockExamsTaken,
                'best_mock_score' => $bestMockScore,
                'subjects_enrolled' => $subjectsEnrolled,
                'has_active_subscription' => $hasActiveSubscription,
                'parent_paid' => $parentPaidForStudent,
                'can_view_metrics' => $canViewMetrics,
                'access_label' => $parentPaidForStudent
                    ? __('✓ You Paid')
                    : ($parent->hasAnyRole(['super-admin', 'admin']) ? __('Staff View') : __('Progress Access')),
                'lessons_completed' => $lessonsCompleted,
                'lessons_started' => $lessonsStarted,
                'lessons_percentage' => $lessonsPercentage,
                'time_spent_seconds' => $timeSpent,
                'time_spent_formatted' => $timeSpentFormatted,
                // Video analytics from Bunny Stream
                'video_views' => $childVideoViews,
                'video_watch_time_seconds' => $childVideoWatchTime,
                'video_watch_time_formatted' => $childVideoWatchTimeFormatted,
                'video_completion_rate' => number_format($childCompletionRate, 1),
            ];
        }
    }

    private function canViewProgressMetrics($user): bool
    {
        return $user->hasAnyRole(['super-admin', 'admin'])
            || (! $user->isParent() && $user->hasActiveSubscription());
    }

    /**
     * Format seconds to human readable format (e.g., "2h 15m")
     */
    private function formatSeconds($seconds): string
    {
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);

        if ($hours > 0) {
            return "{$hours}h {$minutes}m";
        }

        return "{$minutes}m";
    }

    public function render()
    {
        return view('livewire.dashboard.parent-index');
    }
}
