<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\DownloadJambRequest;
use App\Http\Requests\Api\DownloadSubjectRequest;
use App\Http\Resources\Api\QuestionResource;
use App\Http\Resources\Api\SubjectDownloadResponse;
use App\Models\Question;
use App\Models\Subject;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * @group Question Pack Downloads
 *
 * APIs for downloading question packages for offline use.
 */
class SubjectDownloadController extends Controller
{
    /**
     * Download Single Subject Question Package
     *
     * Downloads all questions for a specific subject, optionally filtered by year.
     * Includes all options to prevent N+1 queries.
     *
     * @urlParam id integer required Subject ID. Example: 1
     * @queryParam year integer Optional year filter. Example: 2024
     *
     * @response {
     *   "subject": {
     *     "id": 1,
     *     "name": "Mathematics",
     *     "code": "MATH",
     *     "slug": "mathematics"
     *   },
     *   "questions": [
     *     {
     *       "id": 1,
     *       "question_text": "What is 2+2?",
     *       "options": [...]
     *     }
     *   ],
     *   "total_questions": 100
     * }
     */
    public function downloadSubject(DownloadSubjectRequest $request, int $id): JsonResponse
    {
        try {
            $subject = Subject::with('examTypes')
                ->where('is_active', true)
                ->findOrFail($id);

            $query = Question::with('options')
                ->where('subject_id', $id)
                ->where('is_active', true)
                ->where('status', 'approved')
                ->where('is_mock', false);

            if ($request->has('year')) {
                $query->where('year', $request->year);
            }

            // Chunked loading for mobile (default 50 questions per page)
            $perPage = $request->input('per_page', 50);
            $page = $request->input('page', 1);

            $questions = $query->orderBy('year')
                ->orderBy('id')
                ->paginate($perPage, ['*'], 'page', $page);

            return response()->json([
                'subject' => [
                    'id' => $subject->id,
                    'name' => $subject->name,
                    'code' => $subject->code,
                    'slug' => $subject->slug,
                    'icon' => $subject->icon,
                    'color' => $subject->color,
                ],
                'questions' => QuestionResource::collection($questions),
                'pagination' => [
                    'total_questions' => $questions->total(),
                    'per_page' => $questions->perPage(),
                    'current_page' => $questions->currentPage(),
                    'last_page' => $questions->lastPage(),
                ],
                'year_filter' => $request->year,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Subject download failed', [
                'subject_id' => $id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to download subject questions',
            ], 500);
        }
    }

    /**
     * Download JAMB Practice Package (Multi-Subject)
     *
     * Downloads questions for multiple subjects in JAMB format.
     * Accepts comma-separated subject IDs and optional year filter.
     *
     * @queryParam subjects string required Comma-separated subject IDs. Example: 1,2,3,4
     * @queryParam year integer Optional year filter. Example: 2024
     *
     * @response {
     *   "subjects": [
     *     {
     *       "id": 1,
     *       "name": "Mathematics",
     *       "questions": [...],
     *       "total_questions": 40
     *     }
     *   ],
     *   "total_questions": 160
     * }
     */
    public function downloadJambPractice(DownloadJambRequest $request): JsonResponse
    {
        try {
            $subjectIds = explode(',', $request->subjects);
            $subjectIds = array_map('intval', $subjectIds);

            if (count($subjectIds) < 1 || count($subjectIds) > 4) {
                return response()->json([
                    'message' => 'Please provide between 1 and 4 subject IDs.',
                ], 422);
            }

            $subjects = Subject::whereIn('id', $subjectIds)
                ->where('is_active', true)
                ->get()
                ->keyBy('id');

            $subjectData = [];

            foreach ($subjectIds as $subjectId) {
                if (!isset($subjects[$subjectId])) {
                    continue;
                }

                $subject = $subjects[$subjectId];

                $query = Question::with('options')
                    ->where('subject_id', $subjectId)
                    ->where('is_active', true)
                    ->where('status', 'approved')
                    ->where('is_mock', false);

                if ($request->has('year')) {
                    $query->where('year', $request->year);
                }

                // Chunked loading for mobile (default 50 questions per page)
                $perPage = $request->input('per_page', 50);
                $page = $request->input('page', 1);

                $questions = $query->orderBy('year')
                    ->orderBy('id')
                    ->paginate($perPage, ['*'], 'page', $page);

                $subjectData[] = [
                    'id' => $subject->id,
                    'name' => $subject->name,
                    'code' => $subject->code,
                    'slug' => $subject->slug,
                    'icon' => $subject->icon,
                    'color' => $subject->color,
                    'questions' => QuestionResource::collection($questions),
                    'pagination' => [
                        'total_questions' => $questions->total(),
                        'per_page' => $questions->perPage(),
                        'current_page' => $questions->currentPage(),
                        'last_page' => $questions->lastPage(),
                    ],
                ];
            }

            $totalQuestions = array_sum(array_column($subjectData, 'total_questions'));

            return response()->json([
                'subjects' => $subjectData,
                'total_questions' => $totalQuestions,
                'year_filter' => $request->year,
            ], 200);
        } catch (\Exception $e) {
            Log::error('JAMB download failed', [
                'subjects' => $request->subjects,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Failed to download JAMB questions',
            ], 500);
        }
    }
}
