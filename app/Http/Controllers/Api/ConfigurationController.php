<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\ExamTypeResource;
use App\Http\Resources\Api\SubjectResource;
use App\Models\ExamType;
use App\Models\Question;
use App\Models\Subject;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ConfigurationController extends Controller
{
    /**
     * Get authenticated user's enrolled active subjects.
     */
    public function enrolledSubjects(Request $request): JsonResponse
    {
        $user = $request->user();

        $subjects = $user->enrolledSubjects()
            ->select('subjects.*')
            ->orderBy('subjects.name')
            ->get();

        // Backward-compatible fallback for users that only have selected_subjects populated.
        if ($subjects->isEmpty() && is_array($user->selected_subjects) && ! empty($user->selected_subjects)) {
            $subjects = Subject::query()
                ->where('is_active', true)
                ->whereIn('id', $user->selected_subjects)
                ->orderBy('name')
                ->get();
        }

        return response()->json([
            'message' => 'Subjects retrieved successfully',
            'data' => SubjectResource::collection($subjects),
        ]);
    }

    /**
     * Get all active subjects
     */
    public function subjects(): JsonResponse
    {
        return response()->json([
            'message' => 'Subjects retrieved successfully',
            'data' => SubjectResource::collection(
                Subject::query()->where('is_active', true)->get()
            ),
        ]);
    }

    /**
     * Get all exam types
     */
    public function examTypes(): JsonResponse
    {
        return response()->json([
            'message' => 'Exam types retrieved successfully',
            'data' => ExamTypeResource::collection(ExamType::all()),
        ]);
    }

    /**
     * Get available years for questions
     */
    public function years(): JsonResponse
    {
        $years = Cache::remember('available_years', 3600, function () {
            return Question::query()->whereNotNull('exam_year')
                ->distinct()
                ->orderBy('exam_year', 'desc')
                ->pluck('exam_year')
                ->toArray();
        });

        return response()->json([
            'message' => 'Years retrieved successfully',
            'data' => $years,
        ]);
    }

    /**
     * Get mock exam configuration
     */
    public function mockFormats(): JsonResponse
    {
        $formats = config('mock.formats', []);

        return response()->json([
            'message' => 'Mock formats retrieved successfully',
            'data' => $formats,
        ]);
    }
}
