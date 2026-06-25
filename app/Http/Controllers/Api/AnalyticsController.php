<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function __construct(
        private AnalyticsService $analyticsService
    ) {}

    /**
     * Get user overview statistics
     */
    public function overview(Request $request): JsonResponse
    {
        $user = $request->user();
        $overview = $this->analyticsService->getUserOverview($user);

        return response()->json([
            'message' => 'Analytics overview retrieved successfully',
            'data' => $overview,
        ]);
    }

    /**
     * Get subject performance breakdown
     */
    public function subjectPerformance(Request $request): JsonResponse
    {
        $user = $request->user();
        $performance = $this->analyticsService->getSubjectPerformance($user);

        return response()->json([
            'message' => 'Subject performance retrieved successfully',
            'data' => $performance,
        ]);
    }

    /**
     * Get recent quiz history
     */
    public function quizHistory(Request $request): JsonResponse
    {
        $user = $request->user();
        $limit = $request->get('limit', 10);
        $history = $this->analyticsService->getQuizHistory($user, $limit);

        return response()->json([
            'message' => 'Quiz history retrieved successfully',
            'data' => $history,
        ]);
    }

    /**
     * Get study streak data
     */
    public function studyStreak(Request $request): JsonResponse
    {
        $user = $request->user();
        $streak = $this->analyticsService->calculateStudyStreak($user);

        return response()->json([
            'message' => 'Study streak retrieved successfully',
            'data' => [
                'current_streak' => $streak,
                'last_activity_date' => $user->quizAttempts()->latest()->value('created_at'),
            ],
        ]);
    }
}
