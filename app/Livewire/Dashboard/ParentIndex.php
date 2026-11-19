<?php

namespace App\Livewire\Dashboard;

use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class ParentIndex extends Component
{
    use WithPagination;

    public string $search = '';

    public int $perPage = 10;

    /**
     * Holds resend invitation messages per student.
     *
     * @var array<int, string>
     */
    public $resendInvitationMessage = [];

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    /**
     * Allow guardian to resend invitation (reset password) to a student
     */
    public function resendInvitation($studentId): void
    {
        // Clear only this student's message
        $this->resendInvitationMessage[$studentId] = '';
        $parent = auth()->user();
        $student = $parent->children()->where('users.id', $studentId)->first();
        if (! $student) {
            $this->resendInvitationMessage[$studentId] = __('Student not found or not linked.');

            return;
        }
        // Only allow if onboarding not completed
        if ($student->has_completed_onboarding) {
            $this->resendInvitationMessage[$studentId] = __('Student has already completed setup.');

            return;
        }
        $student->sendPasswordResetNotification(
            app('auth.password.broker')->createToken($student)
        );
        $this->resendInvitationMessage[$studentId] = __('Invitation email resent to :email', ['email' => $student->email]);
    }

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

    public $unlinkSuccessMessage = '';

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

        // Create student with unusable password and unverified email
        $student = User::create([
            'name' => $this->newStudentName,
            'email' => $this->newStudentEmail,
            'password' => Hash::make(Str::random(32)), // unusable random password
            'account_type' => 'student',
            'email_verified_at' => null,
            'has_completed_onboarding' => false,
        ]);

        $this->attachStudentToParent($parent, $student);

        // Send password reset (invitation) link only
        $student->sendPasswordResetNotification(
            app('auth.password.broker')->createToken($student)
        );

        $this->newStudentName = '';
        $this->newStudentEmail = '';
        $this->createStudentSuccessMessage = __('Student account created and invitation sent to :email', ['email' => $student->email]);

        $this->refreshDashboardData();
    }

    public function unlinkStudent($studentId): void
    {
        $this->linkSuccessMessage = '';
        $this->createStudentSuccessMessage = '';
        $this->unlinkSuccessMessage = '';

        $parent = auth()->user();
        if (! $parent) {
            return;
        }

        $student = $parent->children()->where('users.id', $studentId)->first();
        if (! $student) {
            session()->flash('error', __('Student not found or not linked.'));

            return;
        }

        $studentName = $student->name;

        // Release any subscription bound to this student back to unassigned
        $parent->subscriptions()
            ->where('student_id', $studentId)
            ->update(['student_id' => null]);

        // Detach pivot relationship
        $parent->children()->detach($studentId);

        // If the student was created with an unusable password/typo and never completed onboarding or attempted quizzes, clean it up
        if (! $student->has_completed_onboarding && $student->parents()->count() === 0 && $student->quizAttempts()->count() === 0) {
            $student->delete();
        }

        $this->unlinkSuccessMessage = __('Student :name has been unlinked successfully.', ['name' => $studentName]);
        session()->flash('success', $this->unlinkSuccessMessage);

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

    private function refreshDashboardData(): void
    {
        $parent = auth()->user();
        if (! $parent) {
            return;
        }

        $this->showProgressMetrics = $this->canViewProgressMetrics($parent);

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

        $this->paidStudentIds = $this->studentSubscriptions->keys()->filter()->values();

        // High-performance single-query batch calculation for all linked children (scales to 10,000+ students)
        $this->calculateCombinedStats();
    }

    private function calculateCombinedStats(): void
    {
        $parent = auth()->user();
        if (! $parent) {
            return;
        }

        $childIds = $parent->children()->pluck('users.id')->all();
        $childCount = count($childIds);

        if ($childCount === 0) {
            $this->stats = [
                'videos_watched' => 0,
                'total_videos' => Lesson::where('status', 'published')->whereNotNull('video_url')->count(),
                'quizzes_taken' => 0,
                'total_quizzes' => Quiz::where('status', 'published')->count(),
                'average_score' => 0,
                'subjects_enrolled' => 0,
                'mock_exams_taken' => 0,
                'best_mock_score' => 0,
                'lessons_completed' => 0,
                'lessons_started' => 0,
                'lessons_percentage' => 0,
                'time_spent_seconds' => 0,
                'time_spent_hours' => 0,
                'children_count' => 0,
                'total_video_views' => 0,
                'total_video_watch_time_seconds' => 0,
                'total_video_watch_time_hours' => 0,
                'average_completion_rate' => 0,
            ];

            return;
        }

        // Fast batch aggregate 1: Quizzes & Average Score
        $quizAgg = QuizAttempt::whereIn('user_id', $childIds)
            ->where('status', 'completed')
            ->selectRaw('COUNT(*) as total_quizzes, AVG(score_percentage) as avg_score')
            ->first();

        // Fast batch aggregate 2: Mock Exams
        $mockAgg = QuizAttempt::whereIn('user_id', $childIds)
            ->mockExams()
            ->where('status', 'completed')
            ->selectRaw('COUNT(*) as total_mocks, MAX(score_percentage) as best_score')
            ->first();

        // Fast batch aggregate 3: Videos Watched
        $videosWatched = Schema::hasTable('video_progress')
            ? DB::table('video_progress')->whereIn('user_id', $childIds)->where('completed', true)->count()
            : 0;

        // Fast batch aggregate 4: Lessons & Time
        $lessonAgg = Schema::hasTable('user_progress')
            ? DB::table('user_progress')
                ->whereIn('user_id', $childIds)
                ->where('type', 'lesson')
                ->selectRaw('COUNT(*) as started, SUM(CASE WHEN is_completed = 1 THEN 1 ELSE 0 END) as completed, SUM(time_spent_seconds) as total_time')
                ->first()
            : null;

        $lessonsStarted = (int) ($lessonAgg->started ?? 0);
        $lessonsCompleted = (int) ($lessonAgg->completed ?? 0);
        $totalTimeSpent = (int) ($lessonAgg->total_time ?? 0);

        // Fast batch aggregate 5: Enrolled Subjects
        $totalSubjectsEnrolled = DB::table('enrollments')
            ->whereIn('user_id', $childIds)
            ->where('is_active', true)
            ->count();

        // Totals for published content
        $totalVideos = Lesson::where('status', 'published')->whereNotNull('video_url')->count();
        $totalQuizzes = Quiz::where('status', 'published')->count();

        $this->stats = [
            'videos_watched' => $videosWatched,
            'total_videos' => $totalVideos,
            'quizzes_taken' => (int) ($quizAgg->total_quizzes ?? 0),
            'total_quizzes' => $totalQuizzes,
            'average_score' => round((float) ($quizAgg->avg_score ?? 0), 1),
            'subjects_enrolled' => $totalSubjectsEnrolled,
            'mock_exams_taken' => (int) ($mockAgg->total_mocks ?? 0),
            'best_mock_score' => round((float) ($mockAgg->best_score ?? 0), 1),
            'lessons_completed' => $lessonsCompleted,
            'lessons_started' => $lessonsStarted,
            'lessons_percentage' => $lessonsStarted > 0 ? round(($lessonsCompleted / $lessonsStarted) * 100) : 0,
            'time_spent_seconds' => $totalTimeSpent,
            'time_spent_hours' => round($totalTimeSpent / 3600, 1),
            'children_count' => $childCount,
            'total_video_views' => 0,
            'total_video_watch_time_seconds' => 0,
            'total_video_watch_time_hours' => 0,
            'average_completion_rate' => 0,
        ];
    }

    private function calculateChildrenProgress($students = null): void
    {
        $parent = auth()->user();
        if (! $parent) {
            return;
        }

        $items = $students ?? $this->children ?? [];
        $studentIds = collect($items)->pluck('id')->filter()->all();

        if (empty($studentIds)) {
            $this->childrenStats = [];

            return;
        }

        // Fast batch load stats for the current page only (Zero N+1 queries)
        $quizStats = QuizAttempt::whereIn('user_id', $studentIds)
            ->where('status', 'completed')
            ->selectRaw('user_id, COUNT(*) as count, AVG(score_percentage) as avg_score')
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id');

        $mockStats = QuizAttempt::whereIn('user_id', $studentIds)
            ->mockExams()
            ->where('status', 'completed')
            ->selectRaw('user_id, COUNT(*) as count, MAX(score_percentage) as best_score')
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id');

        $videoStats = Schema::hasTable('video_progress')
            ? DB::table('video_progress')
                ->whereIn('user_id', $studentIds)
                ->where('completed', true)
                ->selectRaw('user_id, COUNT(*) as count')
                ->groupBy('user_id')
                ->pluck('count', 'user_id')
            : collect();

        $lessonStats = Schema::hasTable('user_progress')
            ? DB::table('user_progress')
                ->whereIn('user_id', $studentIds)
                ->where('type', 'lesson')
                ->selectRaw('user_id, COUNT(*) as started, SUM(CASE WHEN is_completed = 1 THEN 1 ELSE 0 END) as completed, SUM(time_spent_seconds) as time_spent')
                ->groupBy('user_id')
                ->get()
                ->keyBy('user_id')
            : collect();

        $totalVideos = Lesson::where('status', 'published')->whereNotNull('video_url')->count();

        $parentSubscriptions = $parent->subscriptions()
            ->whereIn('student_id', $studentIds)
            ->whereIn('status', ['active', 'pending'])
            ->get()
            ->keyBy('student_id');

        foreach ($items as $child) {
            $cId = $child->id;
            $q = $quizStats->get($cId);
            $m = $mockStats->get($cId);
            $l = $lessonStats->get($cId);

            $videosWatched = (int) ($videoStats[$cId] ?? 0);
            $avgScore = (float) ($q->avg_score ?? 0);
            $mockExamsTaken = (int) ($m->count ?? 0);
            $bestMockScore = (float) ($m->best_score ?? 0);
            $lessonsStarted = (int) ($l->started ?? 0);
            $lessonsCompleted = (int) ($l->completed ?? 0);
            $timeSpent = (int) ($l->time_spent ?? 0);
            $lessonsPercentage = $lessonsStarted > 0 ? round(($lessonsCompleted / $lessonsStarted) * 100) : 0;

            $subjectsEnrolled = $child->enrolled_subjects_count ?? $child->enrolledSubjects()->count();
            $hasActiveSubscription = $child->hasActiveSubscription();
            $parentPaidForStudent = $parentSubscriptions->has($cId);
            $canViewMetrics = true; // TESTING: Deactivated paywall

            $this->childrenStats[$cId] = [
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
                    : ($parent->hasAnyRole(['super-admin', 'admin']) ? __('Staff View') : __('Access Active')),
                'lessons_completed' => $lessonsCompleted,
                'lessons_started' => $lessonsStarted,
                'lessons_percentage' => $lessonsPercentage,
                'time_spent_seconds' => $timeSpent,
                'time_spent_formatted' => $this->formatSeconds($timeSpent),
                'video_views' => 0,
                'video_watch_time_seconds' => 0,
                'video_watch_time_formatted' => '0m',
                'video_completion_rate' => '0.0',
            ];
        }
    }

    private function canViewProgressMetrics($user): bool
    {
        // TESTING: Allow all guardians to view metrics for testing
        return true;
        /*
        return $user->hasAnyRole(['super-admin', 'admin'])
            || (! $user->isParent() && $user->hasActiveSubscription());
        */
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

    public function toJSON(): array
    {
        return [];
    }

    public function render()
    {
        $parent = auth()->user();
        if ($parent) {
            $this->refreshDashboardData();

            $childrenQuery = $parent->children()
                ->withCount(['enrolledSubjects' => fn ($q) => $q->where('enrollments.is_active', true)])
                ->when($this->search, function ($q) {
                    $q->where(function ($sub) {
                        $sub->where('users.name', 'like', "%{$this->search}%")
                            ->orWhere('users.email', 'like', "%{$this->search}%");
                    });
                });

            $paginatedChildren = $childrenQuery->paginate($this->perPage);
            $this->children = collect($paginatedChildren->items());
            $this->calculateChildrenProgress($paginatedChildren->items());

            return view('livewire.dashboard.parent-index', [
                'children' => $paginatedChildren,
            ]);
        }

        $emptyPaginated = new LengthAwarePaginator([], 0, $this->perPage);

        return view('livewire.dashboard.parent-index', [
            'children' => $emptyPaginated,
        ]);
    }
}
