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
    public function subjects(Request $request): JsonResponse
    {
        $subjectsQuery = Subject::query()
            ->where('is_active', true);

        if ($request->filled('exam_type_id')) {
            $examTypeId = $request->integer('exam_type_id');

            $subjectsQuery->whereHas('questions', function ($query) use ($examTypeId) {
                $query->where('exam_type_id', $examTypeId)
                    ->where('is_active', true)
                    ->where('status', 'approved');
            });
        }

        return response()->json([
            'message' => 'Subjects retrieved successfully',
            'data' => SubjectResource::collection(
                $subjectsQuery->get()
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
    public function years(Request $request): JsonResponse
    {
        $examTypeId = $request->integer('exam_type_id');
        $subjectId = $request->integer('subject_id');
        $cacheKey = sprintf(
            'available_years_exam_type_%s_subject_%s',
            $examTypeId > 0 ? $examTypeId : 'all',
            $subjectId > 0 ? $subjectId : 'all'
        );

        $years = Cache::remember($cacheKey, 3600, function () use ($examTypeId, $subjectId) {
            return Question::query()
                ->where(function ($query) {
                    $query->whereNotNull('exam_year')
                        ->orWhereNotNull('year');
                })
                ->when($examTypeId > 0, function ($query) use ($examTypeId) {
                    $query->where('exam_type_id', $examTypeId);
                })
                ->when($subjectId > 0, function ($query) use ($subjectId) {
                    $query->where('subject_id', $subjectId);
                })
                ->where('is_active', true)
                ->where('status', 'approved')
                ->get(['exam_year', 'year'])
                ->map(fn ($question) => $question->exam_year ?: $question->year)
                ->filter()
                ->unique()
                ->sortDesc()
                ->values()
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
