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
     * Start a new JAMB session and create the attempt
     */
    public function startJambSession(JambSessionRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $jambExamType = ExamType::query()->where('slug', 'jamb')->first();

        if (! $jambExamType) {
            return response()->json(['message' => 'JAMB exam type is not configured.'], 422);
        }

        $questionsPerSubject = (int) ($validated['questions_per_subject'] ?? 40);
        $selectedYear = $validated['year'] ?? null;
        $subjectIds = array_map('intval', $validated['subject_ids']);
        $shuffle = (bool) ($validated['shuffle'] ?? false);

        $subjects = Subject::query()
            ->whereIn('id', $subjectIds)
            ->where('is_active', true)
            ->get();

        if ($subjects->count() !== count($subjectIds)) {
            return response()->json(['message' => 'One or more selected subjects are invalid.'], 422);
        }

        $questionOrder = [];
        $questionsBySubject = [];
        $userAnswers = [];
        $subjectsData = [];

        foreach ($subjects as $subject) {
            $query = Question::query()
                ->where('exam_type_id', $jambExamType->id)
                ->where('subject_id', $subject->id)
                ->where('is_active', true)
                ->where('status', 'approved')
                ->with('options:id,question_id,option_text,option_image,is_correct')
                ->when($selectedYear, function ($q, $year) {
                    $q->where('exam_year', $year);
                });

            if ($shuffle) {
                $query->inRandomOrder();
            }

            $subjectQuestions = $query->limit($questionsPerSubject)->get();

            if ($subjectQuestions->count() < $questionsPerSubject) {
                $yearText = $selectedYear ?: 'all available years';
                return response()->json([
                    'message' => "Not enough questions for {$subject->name} in {$yearText}. Available: {$subjectQuestions->count()}, Required: {$questionsPerSubject}",
                ], 422);
            }

            $mappedQuestions = $subjectQuestions->map(function ($q) use ($shuffle) {
                $options = $q->options->map(function ($opt) {
                    return [
                        'id' => $opt->id,
                        'option_text' => $opt->option_text,
                        'option_text_html' => (string) $opt->option_text_html,
                        'option_image' => $opt->option_image,
                        'is_correct' => $opt->is_correct,
                    ];
                })->toArray();

                if ($shuffle) {
                    shuffle($options);
                }

                return [
                    'id' => $q->id,
                    'question_text' => $q->question_text,
                    'question_text_html' => (string) $q->question_text,
                    'question_image' => $q->question_image,
                    'explanation' => $q->explanation,
                    'explanation_html' => (string) $q->explanation_html,
                    'options' => $options,
                ];
            })->toArray();

            $questionOrder[$subject->id] = $subjectQuestions->pluck('id')->toArray();
            $questionsBySubject[$subject->id] = $mappedQuestions;
            $userAnswers[$subject->id] = array_fill(0, $questionsPerSubject, null);
            $subjectsData[] = [
                'id' => $subject->id,
                'name' => $subject->name,
                'code' => $subject->code,
            ];
        }

        $attempt = \App\Models\QuizAttempt::create([
            'user_id' => auth()->id(),
            'exam_type_id' => $jambExamType->id,
            'exam_year' => $selectedYear,
            'score' => 0,
            'total_questions' => count($subjectIds) * $questionsPerSubject,
            'time_taken_seconds' => 0,
            'time_spent_seconds' => 0,
            'percentage' => 0,
            'started_at' => now(),
            'status' => 'in_progress',
            'question_order' => $questionOrder,
            'current_question_index' => 0,
        ]);

        $payload = [
            'attempt_id' => $attempt->id,
            'time_limit' => $validated['time_limit'] ?? null,
            'current_subject_index' => 0,
            'current_question_index' => 0,
            'questions_by_subject' => $questionsBySubject,
            'user_answers' => $userAnswers,
            'subjects_data' => $subjectsData,
            'elapsed_seconds' => 0,
        ];

        cache()->put("jamb_attempt_{$attempt->id}", $payload, now()->addHours(6));

        return response()->json([
            'message' => 'JAMB session started successfully',
            'data' => $payload,
        ]);
    }

    /**
     * Load an existing JAMB attempt
     */
    public function loadJambAttempt(int $attemptId): JsonResponse
    {
        $attempt = \App\Models\QuizAttempt::findOrFail($attemptId);

        if ($attempt->user_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }

        $cached = cache()->get("jamb_attempt_{$attempt->id}");
        if ($cached) {
            $cached['elapsed_seconds'] = now()->diffInSeconds($attempt->started_at);
            return response()->json([
                'success' => true,
                'data' => $cached,
            ]);
        }

        return response()->json(['message' => 'Session expired or not found in cache. Please restart.'], 404);
    }

    /**
     * Submit JAMB Quiz Answers
     */
    public function submitJambQuiz(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'attempt_id' => 'required|exists:quiz_attempts,id',
            'user_answers' => 'required|array',
        ]);

        $attempt = \App\Models\QuizAttempt::findOrFail($validated['attempt_id']);
        if ($attempt->user_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }

        $userAnswersBySubject = $validated['user_answers'];
        $questionOrder = $attempt->question_order ?? [];
        $correctCount = 0;
        $answeredCount = 0;
        $scoresBySubject = [];

        foreach ($questionOrder as $subjectId => $questionIds) {
            $subjectScore = 0;
            $subjectAnswers = $userAnswersBySubject[$subjectId] ?? [];

            foreach ($questionIds as $index => $questionId) {
                $optionId = $subjectAnswers[$index] ?? null;
                if ($optionId) {
                    $answeredCount++;
                    $question = Question::with('options')->find($questionId);
                    $isCorrect = (bool) ($question?->options->firstWhere('id', $optionId)?->is_correct);
                    
                    if ($isCorrect) {
                        $correctCount++;
                        $subjectScore++;
                    }

                    \App\Models\UserAnswer::updateOrCreate(
                        [
                            'quiz_attempt_id' => $attempt->id,
                            'question_id' => $questionId,
                        ],
                        [
                            'option_id' => $optionId,
                            'is_correct' => $isCorrect,
                            'time_spent_seconds' => 0,
                        ]
                    );
                }
            }
            $scoresBySubject[$subjectId] = $subjectScore;
        }

        $totalQuestions = $attempt->total_questions > 0 ? $attempt->total_questions : 1;
        $percentage = ($correctCount / $totalQuestions) * 100;
        $timeSpent = now()->diffInSeconds($attempt->started_at);

        $attempt->update([
            'score' => $correctCount,
            'percentage' => $percentage,
            'answered_questions' => $answeredCount,
            'correct_answers' => $correctCount,
            'score_percentage' => $percentage,
            'time_spent_seconds' => $timeSpent,
            'time_taken_seconds' => $timeSpent,
            'completed_at' => now(),
            'status' => 'completed',
        ]);

        cache()->forget("jamb_attempt_{$attempt->id}");

        return response()->json([
            'success' => true,
            'data' => [
                'scores_by_subject' => $scoresBySubject,
                'total_score' => $correctCount,
                'percentage' => $percentage,
            ]
        ]);
    }

    /**
     * Exit/Save current state of a JAMB quiz
     */
    public function exitJambQuiz(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'attempt_id' => 'required|exists:quiz_attempts,id',
            'user_answers' => 'required|array',
            'current_subject_index' => 'required|integer',
            'current_question_index' => 'required|integer',
        ]);

        $attempt = \App\Models\QuizAttempt::findOrFail($validated['attempt_id']);
        if ($attempt->user_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }

        $cached = cache()->get("jamb_attempt_{$attempt->id}");
        if ($cached) {
            $cached['user_answers'] = $validated['user_answers'];
            $cached['current_subject_index'] = $validated['current_subject_index'];
            $cached['current_question_index'] = $validated['current_question_index'];
            
            cache()->put("jamb_attempt_{$attempt->id}", $cached, now()->addHours(6));
        }

        return response()->json(['success' => true]);
    }
}
