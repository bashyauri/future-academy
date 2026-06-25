<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\QuizStartRequest;
use App\Http\Requests\Api\QuizSubmitRequest;
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
                'started_at' => $attempt->started_at->toIso8601String(),
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
