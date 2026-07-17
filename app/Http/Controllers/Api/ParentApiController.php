<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Quiz;
use App\Models\Video;
use App\Models\VideoAnalytics;
use App\Models\Subscription;
use App\Models\Subject;
use App\Enums\QuizType;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ParentApiController extends Controller
{
    /**
     * Get parent dashboard combined overview and students list
     */
    public function dashboard(Request $request): JsonResponse
    {
        $parent = $request->user();

        if (!$parent->isParent()) {
            return response()->json(['message' => 'Unauthorized. Parent account required.'], 403);
        }

        // Get linked children (students)
        $children = $parent->children()
            ->with(['enrolledSubjects', 'subscriptions'])
            ->get();

        // Get parent's active/pending subscriptions
        $subscriptions = $parent->subscriptions()
            ->whereIn('status', ['active', 'pending'])
            ->with('student')
            ->latest('created_at')
            ->get();

        // Map subscriptions by student_id for quick lookup
        $studentSubscriptions = $subscriptions
            ->whereNotNull('student_id')
            ->keyBy('student_id');

        $unassignedSubscriptions = $subscriptions
            ->whereNull('student_id')
            ->values();

        $paidStudentIds = $studentSubscriptions->keys()->filter()->values();

        // Combined stats
        $totalVideosWatched = 0;
        $totalTotalVideos = 0;
        $totalQuizzesTaken = 0;
        $totalTotalQuizzes = 0;
        $averageScoreSum = 0;
        $totalMockExamsTaken = 0;
        $bestMockScore = 0;
        $totalSubjectsEnrolled = 0;
        $totalLessonsCompleted = 0;
        $totalLessonsStarted = 0;
        $totalTimeSpent = 0;
        $totalVideoViews = 0;
        $totalVideoWatchTime = 0;
        $averageCompletionRateSum = 0;

        $showProgressMetrics = $parent->hasAnyRole(['super-admin', 'admin']);

        $childrenStats = [];

        foreach ($children as $child) {
            $parentPaidForStudent = $studentSubscriptions->has($child->id);
            $canViewMetrics = $showProgressMetrics || $parentPaidForStudent;

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

            if ($canViewMetrics) {
                $totalVideosWatched += $videosWatched;
                $totalTotalVideos += $totalVideos;
                $totalQuizzesTaken += $quizzesTaken;
                $totalTotalQuizzes += $totalQuizzes;
                $averageScoreSum += $childAvgScore;
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
                $averageCompletionRateSum += $childCompletionRate;
            }

            $childrenStats[] = [
                'id' => $child->id,
                'name' => $child->name,
                'email' => $child->email,
                'has_completed_onboarding' => (bool)$child->has_completed_onboarding,
                'email_verified' => !is_null($child->email_verified_at),
                'enrolled_subjects_count' => $subjectsEnrolled,
                'has_active_subscription' => $hasActiveSubscription,
                'parent_paid' => $parentPaidForStudent,
                'can_view_metrics' => $canViewMetrics,
                'access_label' => $parentPaidForStudent
                    ? 'Paid'
                    : ($showProgressMetrics ? 'Staff View' : 'Locked'),
                'metrics' => $canViewMetrics ? [
                    'videos_watched' => $videosWatched,
                    'total_videos' => $totalVideos,
                    'videos_percentage' => $totalVideos > 0 ? round(($videosWatched / $totalVideos) * 100) : 0,
                    'average_score' => round($childAvgScore, 1),
                    'mock_exams_taken' => $mockExamsTaken,
                    'best_mock_score' => round($bestMock, 1),
                    'lessons_completed' => $lessonsCompleted,
                    'lessons_started' => $lessonsStarted,
                    'lessons_percentage' => $lessonsStarted > 0 ? round(($lessonsCompleted / $lessonsStarted) * 100) : 0,
                    'time_spent_seconds' => $timeSpent,
                    'time_spent_formatted' => $this->formatSeconds($timeSpent),
                    'video_views' => $childVideoViews,
                    'video_watch_time_seconds' => $childVideoWatchTime,
                    'video_watch_time_formatted' => $this->formatSeconds($childVideoWatchTime),
                    'video_completion_rate' => round($childCompletionRate, 1),
                ] : null,
            ];
        }

        $childCount = count($children);
        $paidCount = $paidStudentIds->count();

        $combinedStats = [
            'children_count' => $childCount,
            'paid_count' => $paidCount,
            'videos_watched' => $totalVideosWatched,
            'total_videos' => $totalTotalVideos,
            'quizzes_taken' => $totalQuizzesTaken,
            'total_quizzes' => $totalTotalQuizzes,
            'average_score' => $paidCount > 0 ? round($averageScoreSum / $paidCount, 1) : 0,
            'subjects_enrolled' => $totalSubjectsEnrolled,
            'mock_exams_taken' => $totalMockExamsTaken,
            'best_mock_score' => round($bestMockScore, 1),
            'lessons_completed' => $totalLessonsCompleted,
            'lessons_started' => $totalLessonsStarted,
            'lessons_percentage' => $totalLessonsStarted > 0 ? round(($totalLessonsCompleted / $totalLessonsStarted) * 100) : 0,
            'time_spent_seconds' => $totalTimeSpent,
            'time_spent_hours' => round($totalTimeSpent / 3600, 1),
            'total_video_views' => $totalVideoViews,
            'total_video_watch_time_seconds' => $totalVideoWatchTime,
            'total_video_watch_time_hours' => round($totalVideoWatchTime / 3600, 1),
            'average_completion_rate' => $paidCount > 0 ? round($averageCompletionRateSum / $paidCount, 1) : 0,
        ];

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
                'subscriptions' => $formattedSubscriptions,
                'unassigned_subscriptions' => $unassignedSubscriptions->map(fn($sub) => [
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

        if (!$parent->isParent()) {
            return response()->json(['message' => 'Unauthorized. Parent account required.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $student = User::where('email', $request->email)->first();

        if (!$student || !$student->isStudent()) {
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

        if (!$parent->isParent()) {
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

        if (!$parent->isParent()) {
            return response()->json(['message' => 'Unauthorized. Parent account required.'], 403);
        }

        $student = $parent->children()->where('users.id', $studentId)->first();

        if (!$student) {
            return response()->json(['message' => 'Student not found or not linked.'], 404);
        }

        if ($student->has_completed_onboarding) {
            return response()->json(['message' => 'Student has already completed setup.'], 422);
        }

        $student->sendPasswordResetNotification(
            app('auth.password.broker')->createToken($student)
        );

        return response()->json([
            'message' => 'Invitation email resent to ' . $student->email,
        ]);
    }

    /**
     * Helper to verify if parent has access to child's metrics
     */
    private function checkChildAccess(User $parent, $studentId): ?User
    {
        $student = $parent->children()->where('users.id', $studentId)->first();
        if (!$student) {
            return null;
        }

        // Check if metrics can be viewed
        $parentPaidForStudent = $parent->subscriptions()
            ->where('student_id', $studentId)
            ->where('status', 'active')
            ->exists();

        $showProgressMetrics = $parent->hasAnyRole(['super-admin', 'admin']);

        if (!$showProgressMetrics && !$parentPaidForStudent) {
            return null;
        }

        return $student;
    }

    public function studentOverview(Request $request, $studentId): JsonResponse
    {
        $parent = $request->user();
        if (!$parent->isParent()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $student = $this->checkChildAccess($parent, $studentId);
        if (!$student) {
            return response()->json(['message' => 'Unauthorized or payment required.'], 403);
        }

        $overview = app(\App\Services\AnalyticsService::class)->getUserOverview($student);

        return response()->json([
            'message' => 'Analytics overview retrieved successfully',
            'data' => $overview,
        ]);
    }

    public function studentSubjectPerformance(Request $request, $studentId): JsonResponse
    {
        $parent = $request->user();
        if (!$parent->isParent()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $student = $this->checkChildAccess($parent, $studentId);
        if (!$student) {
            return response()->json(['message' => 'Unauthorized or payment required.'], 403);
        }

        $performance = app(\App\Services\AnalyticsService::class)->getSubjectPerformance($student);

        return response()->json([
            'message' => 'Subject performance retrieved successfully',
            'data' => $performance,
        ]);
    }

    public function studentQuizHistory(Request $request, $studentId): JsonResponse
    {
        $parent = $request->user();
        if (!$parent->isParent()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $student = $this->checkChildAccess($parent, $studentId);
        if (!$student) {
            return response()->json(['message' => 'Unauthorized or payment required.'], 403);
        }

        $limit = $request->get('limit', 10);
        $history = app(\App\Services\AnalyticsService::class)->getQuizHistory($student, $limit);

        return response()->json([
            'message' => 'Quiz history retrieved successfully',
            'data' => $history,
        ]);
    }

    public function studentStudyStreak(Request $request, $studentId): JsonResponse
    {
        $parent = $request->user();
        if (!$parent->isParent()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $student = $this->checkChildAccess($parent, $studentId);
        if (!$student) {
            return response()->json(['message' => 'Unauthorized or payment required.'], 403);
        }

        $streak = app(\App\Services\AnalyticsService::class)->calculateStudyStreak($student);

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
        if (!$parent->isParent()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $student = $parent->children()->where('users.id', $studentId)->first();
        if (!$student) {
            return response()->json(['message' => 'Student not found.'], 404);
        }

        // Get all active subjects
        $subjects = Subject::where('is_active', true)->get()->map(function($subject) use ($student) {
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
        if (!$parent->isParent()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $student = $parent->children()->where('users.id', $studentId)->first();
        if (!$student) {
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
