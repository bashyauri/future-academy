<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LessonProgressRequest;
use App\Services\LessonService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LessonController extends Controller
{
    public function __construct(
        private LessonService $lessonService
    ) {}

    /**
     * Get lessons for a subject
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'subject_id' => 'required|exists:subjects,id',
        ]);

        $user = $request->user();
        $lessons = $this->lessonService->getLessonsForSubject($user, $request->subject_id);

        return response()->json([
            'message' => 'Lessons retrieved successfully',
            'data' => $lessons,
        ]);
    }

    /**
     * Get lesson details
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $lesson = $this->lessonService->getLessonDetails($user, $id);

        return response()->json([
            'message' => 'Lesson details retrieved successfully',
            'data' => $lesson,
        ]);
    }

    /**
     * Update lesson progress
     */
    public function updateProgress(LessonProgressRequest $request, int $id): JsonResponse
    {
        $user = $request->user();
        $progress = $this->lessonService->updateProgress($user, $id, $request->validated());

        return response()->json([
            'message' => 'Lesson progress updated successfully',
            'data' => [
                'lesson_id' => $progress->lesson_id,
                'progress_percentage' => $progress->progress_percentage,
                'is_completed' => $progress->is_completed,
            ],
        ]);
    }

    /**
     * Mark lesson as completed
     */
    public function complete(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $progress = $this->lessonService->markAsCompleted($user, $id);

        return response()->json([
            'message' => 'Lesson marked as completed successfully',
            'data' => [
                'lesson_id' => $progress->lesson_id,
                'is_completed' => $progress->is_completed,
                'completed_at' => $progress->updated_at,
            ],
        ]);
    }
}
