<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\JambSessionRequest;
use App\Http\Requests\Api\QuizStartRequest;
use App\Http\Requests\Api\QuizSubmitRequest;
use App\Models\ExamType;
use App\Models\Question;
use App\Models\Subject;
use App\Services\QuizService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuizController extends Controller
{
    public function __construct(
        private QuizService $quizService
    ) {}

    /**
     * Get available quizzes
     */
    public function index(Request $request): JsonResponse
    {
        $subjectId = $request->get('subject_id');
        $type = $request->get('type');
        $quizzes = $this->quizService->getQuizzes($subjectId, $type);

        return response()->json([
            'message' => 'Quizzes retrieved successfully',
            'data' => $quizzes,
        ]);
    }

    /**
     * Get quiz details
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $quiz = $this->quizService->getQuizDetails($id);

        return response()->json([
            'message' => 'Quiz details retrieved successfully',
            'data' => $quiz,
        ]);
    }

    /**
     * Start a new quiz attempt
     */
    public function start(QuizStartRequest $request, int $id): JsonResponse
    {
        $user = $request->user();
        $options = $request->validated();
        $attempt = $this->quizService->startQuiz($user, $id, $options);

        return response()->json([
            'message' => 'Quiz started successfully',
            'data' => [
                'attempt_id' => $attempt->id,
                'quiz_id' => $attempt->quiz_id,
                'total_questions' => $attempt->total_questions,
                'question_order' => $attempt->question_order,
                'time_limit' => $request->validated('time_limit'),
                'started_at' => $attempt->started_at->toIso8601String(),
            ],
        ]);
    }

    /**
     * Initialize JAMB session settings with web-equivalent validations.
     */
    public function initializeJambSession(JambSessionRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $jambExamType = ExamType::query()->where('slug', 'jamb')->first();

        if (! $jambExamType) {
            return response()->json([
                'message' => 'JAMB exam type is not configured.',
            ], 422);
        }

        $questionsPerSubject = (int) ($validated['questions_per_subject'] ?? 40);
        $selectedYear = $validated['year'] ?? null;
        $subjectIds = array_map('intval', $validated['subject_ids']);

        $subjects = Subject::query()
            ->whereIn('id', $subjectIds)
            ->where('is_active', true)
            ->get();

        if ($subjects->count() !== count($subjectIds)) {
            return response()->json([
                'message' => 'One or more selected subjects are invalid.',
            ], 422);
        }

        foreach ($subjects as $subject) {
            $questionCount = Question::query()
                ->where('exam_type_id', $jambExamType->id)
                ->where('subject_id', $subject->id)
                ->where('is_active', true)
                ->where('status', 'approved')
                ->when($selectedYear, function ($query, $year) {
                    $query->where('exam_year', $year);
                })
                ->count();

            if ($questionCount < $questionsPerSubject) {
                $yearText = $selectedYear ?: 'all available years';

                return response()->json([
                    'message' => "Not enough questions for {$subject->name} in {$yearText}. Available: {$questionCount}, Required: {$questionsPerSubject}",
                ], 422);
            }
        }

        return response()->json([
            'message' => 'JAMB session initialized successfully',
            'data' => [
                'exam_type_id' => $jambExamType->id,
                'year' => $selectedYear,
                'subject_ids' => $subjectIds,
                'questions_per_subject' => $questionsPerSubject,
                'time_limit' => $validated['time_limit'] ?? null,
                'shuffle' => (bool) ($validated['shuffle'] ?? false),
            ],
        ]);
    }

    /**
     * Submit quiz answers
     */
    public function submitAnswers(QuizSubmitRequest $request, int $id): JsonResponse
    {
        $user = $request->user();
        $answers = $request->validated()['answers'];
        $results = $this->quizService->submitAnswers($user, $id, $answers);

        return response()->json([
            'message' => 'Quiz submitted successfully',
            'data' => $results,
        ]);
    }

    /**
     * Get attempt results
     */
    public function results(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $results = $this->quizService->getAttemptResults($user, $id);

        return response()->json([
            'message' => 'Quiz results retrieved successfully',
            'data' => $results,
        ]);
    }
}
