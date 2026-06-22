<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\MockGroupRequest;
use App\Http\Requests\Api\MockSessionRequest;
use App\Http\Resources\Api\MockGroupResource;
use App\Http\Resources\Api\MockSessionResource;
use App\Http\Resources\Api\QuestionResource;
use App\Models\ExamType;
use App\Models\MockGroup;
use App\Models\Question;
use App\Models\Subject;
use App\Services\MockGroupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MockExamController extends Controller
{
    public function __construct(
        private MockGroupService $mockGroupService
    ) {}

    /**
     * Get all mock groups for a subject and exam type.
     *
     * @param MockGroupRequest $request
     * @return JsonResponse
     */
    public function index(MockGroupRequest $request): JsonResponse
    {
        try {
            $subject = Subject::findOrFail($request->subject_id);
            $examType = ExamType::findOrFail($request->exam_type_id);

            $mockGroups = $this->mockGroupService->getMockGroups($subject, $examType);

            return response()->json([
                'message' => 'Mock groups retrieved successfully',
                'data' => MockGroupResource::collection($mockGroups),
            ], 200);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve mock groups', [
                'subject_id' => $request->subject_id,
                'exam_type_id' => $request->exam_type_id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to retrieve mock groups',
            ], 500);
        }
    }

    /**
     * Get a specific mock group by batch number.
     *
     * @param MockGroupRequest $request
     * @param int $batchNumber
     * @return JsonResponse
     */
    public function show(MockGroupRequest $request, int $batchNumber): JsonResponse
    {
        try {
            $subject = Subject::findOrFail($request->subject_id);
            $examType = ExamType::findOrFail($request->exam_type_id);

            $mockGroup = $this->mockGroupService->getMockGroupByBatchNumber(
                $subject,
                $examType,
                $batchNumber
            );

            if (!$mockGroup) {
                return response()->json([
                    'message' => 'Mock group not found',
                ], 404);
            }

            return response()->json([
                'message' => 'Mock group retrieved successfully',
                'data' => new MockGroupResource($mockGroup),
            ], 200);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve mock group', [
                'subject_id' => $request->subject_id,
                'exam_type_id' => $request->exam_type_id,
                'batch_number' => $batchNumber,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to retrieve mock group',
            ], 500);
        }
    }

    /**
     * Download questions for a specific mock group.
     *
     * @param MockGroupRequest $request
     * @param int $batchNumber
     * @return JsonResponse
     */
    public function download(MockGroupRequest $request, int $batchNumber): JsonResponse
    {
        try {
            $subject = Subject::findOrFail($request->subject_id);
            $examType = ExamType::findOrFail($request->exam_type_id);

            $mockGroup = $this->mockGroupService->getMockGroupByBatchNumber(
                $subject,
                $examType,
                $batchNumber
            );

            if (!$mockGroup) {
                return response()->json([
                    'message' => 'Mock group not found',
                ], 404);
            }

            $questions = $this->mockGroupService->getGroupQuestions($mockGroup);

            return response()->json([
                'message' => 'Mock group questions downloaded successfully',
                'data' => [
                    'mock_group' => new MockGroupResource($mockGroup),
                    'questions' => QuestionResource::collection($questions),
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Failed to download mock group questions', [
                'subject_id' => $request->subject_id,
                'exam_type_id' => $request->exam_type_id,
                'batch_number' => $batchNumber,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to download mock group questions',
            ], 500);
        }
    }

    /**
     * Initialize a multi-subject mock session.
     *
     * @param MockSessionRequest $request
     * @return JsonResponse
     */
    public function initializeSession(MockSessionRequest $request): JsonResponse
    {
        try {
            $examType = ExamType::findOrFail($request->exam_type_id);
            $subjects = Subject::whereIn('id', $request->subject_ids)->get();

            if ($subjects->count() !== count($request->subject_ids)) {
                return response()->json([
                    'message' => 'One or more subjects not found',
                ], 404);
            }

            $durationMinutes = $request->duration_minutes ?? 120;
            $timeLimitPerSubject = intdiv($durationMinutes, $subjects->count());

            $subjectsData = [];
            $totalQuestions = 0;

            foreach ($subjects as $subject) {
                $mockGroups = $this->mockGroupService->getMockGroups($subject, $examType);
                $firstGroup = $this->mockGroupService->getFirstGroup($subject, $examType);

                $subjectsData[] = [
                    'id' => $subject->id,
                    'name' => $subject->name,
                    'code' => $subject->code,
                    'slug' => $subject->slug,
                    'icon' => $subject->icon,
                    'color' => $subject->color,
                    'total_groups' => $mockGroups->count(),
                    'first_group' => $firstGroup ? new MockGroupResource($firstGroup) : null,
                    'time_limit_minutes' => $timeLimitPerSubject,
                ];

                if ($firstGroup) {
                    $totalQuestions += $firstGroup->total_questions;
                }
            }

            $sessionData = [
                'session_id' => str()->uuid(),
                'exam_type' => [
                    'id' => $examType->id,
                    'name' => $examType->name,
                    'slug' => $examType->slug,
                ],
                'subjects' => $subjectsData,
                'duration_minutes' => $durationMinutes,
                'total_questions' => $totalQuestions,
                'time_limit_per_subject' => $timeLimitPerSubject,
                'created_at' => now()->toIso8601String(),
            ];

            return response()->json([
                'message' => 'Mock session initialized successfully',
                'data' => new MockSessionResource($sessionData),
            ], 200);
        } catch (\Exception $e) {
            Log::error('Failed to initialize mock session', [
                'subject_ids' => $request->subject_ids,
                'exam_type_id' => $request->exam_type_id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to initialize mock session',
            ], 500);
        }
    }
}
