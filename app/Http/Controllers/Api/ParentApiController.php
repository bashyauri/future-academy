<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\Subject;
use App\Models\Subscription;
use App\Models\User;
use App\Services\AnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ParentApiController extends Controller
{
    /**
     * Get parent dashboard combined overview and students list
     */
    public function dashboard(Request $request): JsonResponse
    {
        $parent = $request->user();

        if (! $parent->isParent()) {
            return response()->json(['message' => 'Unauthorized. Parent account required.'], 403);
        }

        $childIds = $parent->children()->pluck('users.id')->all();
        $childCount = count($childIds);

        // Get parent's active/pending subscriptions
        $subscriptions = $parent->subscriptions()
            ->whereIn('status', ['active', 'pending'])
            ->with('student')
            ->latest('created_at')
            ->get();

        $studentSubscriptions = $subscriptions
            ->whereNotNull('student_id')
            ->keyBy('student_id');

        $unassignedSubscriptions = $subscriptions
            ->whereNull('student_id')
            ->values();

        $paidStudentIds = $studentSubscriptions->keys()->filter()->values();

        // 1. High-performance batch aggregates across all students (scales to 10,000+ students)
        if ($childCount === 0) {
            $combinedStats = [
                'children_count' => 0,
                'paid_count' => 0,
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
                'total_video_views' => 0,
                'total_video_watch_time_seconds' => 0,
                'total_video_watch_time_hours' => 0,
                'average_completion_rate' => 0,
            ];
            $childrenStats = [];
            $paginationMeta = [
                'current_page' => 1,
                'last_page' => 1,
                'per_page' => 50,
                'total' => 0,
                'has_more' => false,
            ];
        } else {
            $quizAgg = QuizAttempt::whereIn('user_id', $childIds)
                ->where('status', 'completed')
                ->selectRaw('COUNT(*) as total_quizzes, AVG(score_percentage) as avg_score')
                ->first();

            $mockAgg = QuizAttempt::whereIn('user_id', $childIds)
                ->mockExams()
                ->where('status', 'completed')
                ->selectRaw('COUNT(*) as total_mocks, MAX(score_percentage) as best_score')
                ->first();

            $videosWatched = Schema::hasTable('video_progress')
                ? DB::table('video_progress')->whereIn('user_id', $childIds)->where('completed', true)->count()
                : 0;

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

            $totalSubjectsEnrolled = DB::table('enrollments')
                ->whereIn('user_id', $childIds)
                ->where('is_active', true)
                ->count();

            $totalVideos = Lesson::where('status', 'published')->whereNotNull('video_url')->count();
            $totalQuizzes = Quiz::where('status', 'published')->count();

            $combinedStats = [
                'children_count' => $childCount,
                'paid_count' => $paidStudentIds->count(),
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
                'total_video_views' => 0,
                'total_video_watch_time_seconds' => 0,
                'total_video_watch_time_hours' => 0,
                'average_completion_rate' => 0,
            ];

            // 2. Batch load students (supports pagination & search for large schools)
            $perPage = min((int) $request->get('per_page', 50), 100);
            $search = $request->get('search');

            $studentsQuery = $parent->children()
                ->withCount(['enrolledSubjects' => fn ($q) => $q->where('enrollments.is_active', true)])
                ->when($search, function ($q) use ($search) {
                    $q->where(function ($sub) use ($search) {
                        $sub->where('users.name', 'like', "%{$search}%")
                            ->orWhere('users.email', 'like', "%{$search}%");
                    });
                });

            $paginated = $studentsQuery->paginate($perPage);
            $pageStudents = $paginated->items();
            $pageStudentIds = collect($pageStudents)->pluck('id')->all();

            // Batch load stats for only the current students
            $quizStats = QuizAttempt::whereIn('user_id', $pageStudentIds)
                ->where('status', 'completed')
                ->selectRaw('user_id, COUNT(*) as count, AVG(score_percentage) as avg_score')
                ->groupBy('user_id')
                ->get()
                ->keyBy('user_id');

            $mockStats = QuizAttempt::whereIn('user_id', $pageStudentIds)
                ->mockExams()
                ->where('status', 'completed')
                ->selectRaw('user_id, COUNT(*) as count, MAX(score_percentage) as best_score')
                ->groupBy('user_id')
                ->get()
                ->keyBy('user_id');

            $videoStats = Schema::hasTable('video_progress')
                ? DB::table('video_progress')
                    ->whereIn('user_id', $pageStudentIds)
                    ->where('completed', true)
                    ->selectRaw('user_id, COUNT(*) as count')
                    ->groupBy('user_id')
                    ->pluck('count', 'user_id')
                : collect();

            $lessonStats = Schema::hasTable('user_progress')
                ? DB::table('user_progress')
                    ->whereIn('user_id', $pageStudentIds)
                    ->where('type', 'lesson')
                    ->selectRaw('user_id, COUNT(*) as started, SUM(CASE WHEN is_completed = 1 THEN 1 ELSE 0 END) as completed, SUM(time_spent_seconds) as time_spent')
                    ->groupBy('user_id')
                    ->get()
                    ->keyBy('user_id')
                : collect();

            $childrenStats = [];
            foreach ($pageStudents as $child) {
                $cId = $child->id;
                $q = $quizStats->get($cId);
                $m = $mockStats->get($cId);
                $l = $lessonStats->get($cId);

                $vWatched = (int) ($videoStats[$cId] ?? 0);
                $avgSc = (float) ($q->avg_score ?? 0);
                $mTaken = (int) ($m->count ?? 0);
                $bMock = (float) ($m->best_score ?? 0);
                $lStarted = (int) ($l->started ?? 0);
                $lCompleted = (int) ($l->completed ?? 0);
                $tSpent = (int) ($l->time_spent ?? 0);
                $lPercentage = $lStarted > 0 ? round(($lCompleted / $lStarted) * 100) : 0;
                $parentPaid = $studentSubscriptions->has($cId);

                $childrenStats[] = [
                    'id' => $child->id,
                    'name' => $child->name,
                    'email' => $child->email,
                    'has_completed_onboarding' => (bool) $child->has_completed_onboarding,
                    'email_verified' => ! is_null($child->email_verified_at),
                    'enrolled_subjects_count' => (int) ($child->enrolled_subjects_count ?? 0),
                    'has_active_subscription' => $child->hasActiveSubscription(),
                    'parent_paid' => $parentPaid,
                    'can_view_metrics' => true,
                    'access_label' => $parentPaid ? 'Paid' : 'Access Active',
                    'metrics' => [
                        'videos_watched' => $vWatched,
                        'total_videos' => $totalVideos,
                        'videos_percentage' => $totalVideos > 0 ? round(($vWatched / $totalVideos) * 100) : 0,
                        'average_score' => round($avgSc, 1),
                        'mock_exams_taken' => $mTaken,
                        'best_mock_score' => round($bMock, 1),
                        'lessons_completed' => $lCompleted,
                        'lessons_started' => $lStarted,
                        'lessons_percentage' => $lPercentage,
                        'time_spent_seconds' => $tSpent,
                        'time_spent_formatted' => $this->formatSeconds($tSpent),
                        'video_views' => 0,
                        'video_watch_time_seconds' => 0,
                        'video_watch_time_formatted' => '0m',
                        'video_completion_rate' => 0.0,
                    ],
                ];
            }

            $paginationMeta = [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
                'has_more' => $paginated->hasMorePages(),
            ];
        }

        // Format subscriptions
        $formattedSubscriptions = $subscriptions->map(function ($sub) {
            return [
                'id' => $sub->id,
                'plan' => $sub->plan,
                'amount' => $sub->amount,
                'status' => $sub->status,
                'student_id' => $sub->student_id,
                'student_name' => $sub->student?->name,
                'created_at' => $sub->created_at->toIso8601String(),
                'ends_at' => $sub->ends_at ? $sub->ends_at->toIso8601String() : null,
                'is_expired' => $sub->ends_at ? $sub->ends_at->isPast() : false,
                'type' => $sub->type ?? 'one-time',
            ];
        });

        return response()->json([
            'message' => 'Dashboard data retrieved successfully',
            'data' => [
                'stats' => $combinedStats,
                'children' => $childrenStats,
                'pagination' => $paginationMeta,
                'subscriptions' => $formattedSubscriptions,
                'unassigned_subscriptions' => $unassignedSubscriptions->map(fn ($sub) => [
                    'id' => $sub->id,
                    'plan' => $sub->plan,
                    'amount' => $sub->amount,
                    'status' => $sub->status,
                    'created_at' => $sub->created_at->toIso8601String(),
                ]),
            ],
        ]);
    }

    /**
     * Link an existing student to the parent
     */
    public function linkStudent(Request $request): JsonResponse
    {
        $parent = $request->user();

        if (! $parent->isParent()) {
            return response()->json(['message' => 'Unauthorized. Parent account required.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $student = User::where('email', $request->email)->first();

        if (! $student || ! $student->isStudent()) {
            return response()->json(['message' => 'Student not found.'], 404);
        }

        if ($student->id === $parent->id) {
            return response()->json(['message' => 'You cannot link your own account.'], 422);
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

        return response()->json([
            'message' => 'Student linked successfully',
            'student' => [
                'id' => $student->id,
                'name' => $student->name,
                'email' => $student->email,
            ],
        ]);
    }

    /**
     * Create and link a new student
     */
    public function createStudent(Request $request): JsonResponse
    {
        $parent = $request->user();

        if (! $parent->isParent()) {
            return response()->json(['message' => 'Unauthorized. Parent account required.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Create student with unusable password and unverified email
        $student = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make(Str::random(32)),
            'account_type' => 'student',
            'email_verified_at' => null,
            'has_completed_onboarding' => false,
        ]);

        $parent->children()->syncWithoutDetaching([
            $student->id => [
                'is_active' => true,
                'linked_at' => now(),
            ],
        ]);

        // Send password reset (invitation) link
        $student->sendPasswordResetNotification(
            app('auth.password.broker')->createToken($student)
        );

        return response()->json([
            'message' => 'Student account created and invitation sent successfully.',
            'student' => [
                'id' => $student->id,
                'name' => $student->name,
                'email' => $student->email,
            ],
        ], 201);
    }

    /**
     * Resend invitation email to student
     */
    public function resendInvitation(Request $request, $studentId): JsonResponse
    {
        $parent = $request->user();

        if (! $parent->isParent()) {
            return response()->json(['message' => 'Unauthorized. Parent account required.'], 403);
        }

        $student = $parent->children()->where('users.id', $studentId)->first();

        if (! $student) {
            return response()->json(['message' => 'Student not found or not linked.'], 404);
        }

        if ($student->has_completed_onboarding) {
            return response()->json(['message' => 'Student has already completed setup.'], 422);
        }

        $student->sendPasswordResetNotification(
            app('auth.password.broker')->createToken($student)
        );

        return response()->json([
            'message' => 'Invitation email resent to '.$student->email,
        ]);
    }

    /**
     * Helper to verify if parent has access to child's metrics
     */
    private function checkChildAccess(User $parent, $studentId): ?User
    {
        $student = $parent->children()->where('users.id', $studentId)->first();
        if (! $student) {
            return null;
        }

        // TESTING: Bypass payment check for testing so parent can view student analytics
        return $student;
        /*
        $parentPaidForStudent = $parent->subscriptions()
            ->where('student_id', $studentId)
            ->where('status', 'active')
            ->exists();

        $showProgressMetrics = $parent->hasAnyRole(['super-admin', 'admin']);

        if (! $showProgressMetrics && ! $parentPaidForStudent) {
            return null;
        }

        return $student;
        */
    }

    public function studentOverview(Request $request, $studentId): JsonResponse
    {
        $parent = $request->user();
        if (! $parent->isParent()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $student = $this->checkChildAccess($parent, $studentId);
        if (! $student) {
            return response()->json(['message' => 'Unauthorized or payment required.'], 403);
        }

        $overview = app(AnalyticsService::class)->getUserOverview($student);

        return response()->json([
            'message' => 'Analytics overview retrieved successfully',
            'data' => $overview,
        ]);
    }

    public function studentSubjectPerformance(Request $request, $studentId): JsonResponse
    {
        $parent = $request->user();
        if (! $parent->isParent()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $student = $this->checkChildAccess($parent, $studentId);
        if (! $student) {
            return response()->json(['message' => 'Unauthorized or payment required.'], 403);
        }

        $performance = app(AnalyticsService::class)->getSubjectPerformance($student);

        return response()->json([
            'message' => 'Subject performance retrieved successfully',
            'data' => $performance,
        ]);
    }

    public function studentQuizHistory(Request $request, $studentId): JsonResponse
    {
        $parent = $request->user();
        if (! $parent->isParent()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $student = $this->checkChildAccess($parent, $studentId);
        if (! $student) {
            return response()->json(['message' => 'Unauthorized or payment required.'], 403);
        }

        $limit = $request->get('limit', 10);
        $history = app(AnalyticsService::class)->getQuizHistory($student, $limit);

        return response()->json([
            'message' => 'Quiz history retrieved successfully',
            'data' => $history,
        ]);
    }

    public function studentStudyStreak(Request $request, $studentId): JsonResponse
    {
        $parent = $request->user();
        if (! $parent->isParent()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $student = $this->checkChildAccess($parent, $studentId);
        if (! $student) {
            return response()->json(['message' => 'Unauthorized or payment required.'], 403);
        }

        $streak = app(AnalyticsService::class)->calculateStudyStreak($student);

        return response()->json([
            'message' => 'Study streak retrieved successfully',
            'data' => [
                'current_streak' => $streak,
                'last_activity_date' => $student->quizAttempts()->latest()->value('created_at'),
            ],
        ]);
    }

    /**
     * Get enrolled & other available subjects for managing child's enrollment
     */
    public function studentSubjects(Request $request, $studentId): JsonResponse
    {
        $parent = $request->user();
        if (! $parent->isParent()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $student = $parent->children()->where('users.id', $studentId)->first();
        if (! $student) {
            return response()->json(['message' => 'Student not found.'], 404);
        }

        // Get all active subjects
        $subjects = Subject::where('is_active', true)->get()->map(function ($subject) use ($student) {
            $isEnrolled = $student->enrolledSubjects()->where('subjects.id', $subject->id)->exists();

            return [
                'id' => $subject->id,
                'name' => $subject->name,
                'code' => $subject->code,
                'slug' => $subject->slug,
                'is_enrolled' => $isEnrolled,
            ];
        });

        return response()->json([
            'message' => 'Student subjects retrieved successfully',
            'data' => $subjects,
        ]);
    }

    /**
     * Update subject enrollment for linked student
     */
    public function studentUpdateEnrollment(Request $request, $studentId): JsonResponse
    {
        $parent = $request->user();
        if (! $parent->isParent()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $student = $parent->children()->where('users.id', $studentId)->first();
        if (! $student) {
            return response()->json(['message' => 'Student not found.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'subjects' => ['required', 'array'],
            'subjects.*' => ['integer', 'exists:subjects,id'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Deactivate old enrollments
        $student->enrollments()->update(['is_active' => false]);

        // Enroll in new subjects
        foreach ($request->subjects as $subjectId) {
            $student->enrollments()->updateOrCreate(
                ['subject_id' => $subjectId],
                ['is_active' => true, 'enrolled_at' => now()]
            );
        }

        // Fire event to clear cache or notify
        event('student-enrollment-changed', ['studentId' => $student->id]);

        return response()->json([
            'message' => 'Student enrollment updated successfully.',
        ]);
    }

    private function formatSeconds($seconds): string
    {
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);

        if ($hours > 0) {
            return "{$hours}h {$minutes}m";
        }

        return "{$minutes}m";
    }
}
