<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\ExamTypeResource;
use App\Http\Resources\Api\SubjectResource;
use App\Models\ExamType;
use App\Models\Question;
use App\Models\Subject;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class ConfigurationController extends Controller
{
    /**
     * Get all active subjects
     */
    public function subjects(): JsonResponse
    {
        return response()->json([
            'message' => 'Subjects retrieved successfully',
            'data' => SubjectResource::collection(
                Subject::where('is_active', true)->get()
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
            return Question::whereNotNull('exam_year')
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
