<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\MockGroupRequest;
use App\Http\Requests\Api\MockSessionProgressRequest;
use App\Http\Requests\Api\MockSessionRequest;
use App\Http\Requests\Api\MockSubjectsRequest;
use App\Http\Resources\Api\MockGroupResource;
use App\Http\Resources\Api\MockSessionResource;
use App\Http\Resources\Api\QuestionResource;
use App\Models\ExamType;
use App\Models\MockGroup;
use App\Models\MockSession;
use App\Models\Option;
use App\Models\Question;
use App\Models\QuizAttempt;
use App\Models\Subject;
use App\Models\UserAnswer;
use App\Services\MockGroupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class MockExamController extends Controller
{
    public function __construct(
        private MockGroupService $mockGroupService
    ) {}

    /**
     * Get active subjects that contain mock questions for an exam type.
     */
    public function subjects(MockSubjectsRequest $request): JsonResponse
    {
        $examType = ExamType::findOrFail($request->integer('exam_type_id'));

        $questionCounts = Question::query()
            ->select('subject_id')
            ->selectRaw('COUNT(*) as question_count')
            ->where('exam_type_id', $examType->id)
            ->where('is_mock', true)
            ->where('is_active', true)
            ->where('status', 'approved')
            ->whereHas('options')
            ->groupBy('subject_id')
            ->pluck('question_count', 'subject_id');

        $subjects = Subject::query()
            ->where('is_active', true)
            ->whereIn('id', $questionCounts->keys())
            ->orderBy('name')
            ->get()
            ->map(function (Subject $subject) use ($examType, $questionCounts): array {
                $specification = $this->mockGroupService->getSubjectMockSpecification($examType, $subject);

                return [
                    'id' => $subject->id,
                    'name' => $subject->name,
                    'code' => $subject->code,
                    'slug' => $subject->slug,
                    'icon' => $subject->icon,
                    'color' => $subject->color,
                    'question_count' => $specification['questions'],
                    'time_limit_minutes' => $specification['time'],
                    'available_mock_questions' => (int) $questionCounts->get($subject->id, 0),
                ];
            })
            ->values();

        return response()->json([
            'message' => 'Mock subjects retrieved successfully',
            'data' => $subjects,
        ]);
    }

    /**
     * Get all mock groups for a subject and exam type.
     */
    public function index(MockGroupRequest $request): JsonResponse
    {
        try {
            $subject = Subject::findOrFail($request->subject_id);
            $examType = ExamType::findOrFail($request->exam_type_id);

            $mockGroups = $this->mockGroupService->getMockGroups($subject, $examType);

            if ($mockGroups->isEmpty()) {
                $this->mockGroupService->groupMockQuestions($subject, $examType);
                $mockGroups = $this->mockGroupService->getMockGroups($subject, $examType);
            }

            $bestScores = QuizAttempt::query()
                ->where('user_id', $request->user()->id)
                ->whereIn('mock_group_id', $mockGroups->pluck('id'))
                ->where('status', 'completed')
                ->select('mock_group_id')
                ->selectRaw('MAX(percentage) as best_score')
                ->groupBy('mock_group_id')
                ->pluck('best_score', 'mock_group_id');

            $groups = $mockGroups
                ->filter(fn (MockGroup $group): bool => $this->mockGroupService->getGroupQuestions($group)->isNotEmpty())
                ->map(function (MockGroup $group) use ($bestScores): array {
                    return array_merge((new MockGroupResource($group))->resolve(), [
                        'total_questions' => $this->mockGroupService->getGroupQuestions($group)->count(),
                        'is_completed' => $bestScores->has($group->id),
                        'best_score' => $bestScores->get($group->id),
                    ]);
                })
                ->values();

            return response()->json([
                'message' => 'Mock groups retrieved successfully',
                'data' => $groups,
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

            if (! $mockGroup) {
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

            if (! $mockGroup) {
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
     */
    public function initializeSession(MockSessionRequest $request): JsonResponse
    {
        try {
            $examType = ExamType::findOrFail($request->exam_type_id);
            $subjects = Subject::where('is_active', true)
                ->whereIn('id', $request->subject_ids)
                ->get()
                ->keyBy('id');

            if ($subjects->count() !== count($request->subject_ids)) {
                return response()->json([
                    'message' => 'One or more selected subjects are unavailable',
                ], 404);
            }

            $subjectsData = [];
            $questionsPerSubject = [];
            $questionIdsBySubject = [];
            $optionOrder = [];
            $subjectIds = array_map('intval', $request->subject_ids);
            $mockGroup = null;
            $durationMinutes = 0;

            if ($request->filled('mock_group_id')) {
                if (count($subjectIds) !== 1) {
                    throw ValidationException::withMessages([
                        'subject_ids' => ['A subject batch must contain exactly one subject.'],
                    ]);
                }

                $mockGroup = MockGroup::query()
                    ->whereKey($request->integer('mock_group_id'))
                    ->where('subject_id', $subjectIds[0])
                    ->where('exam_type_id', $examType->id)
                    ->first();

                if (! $mockGroup) {
                    return response()->json(['message' => 'Mock group not found.'], 404);
                }

                $batchQuestions = $this->mockGroupService->getGroupQuestions($mockGroup)->shuffle()->values();
                if ($batchQuestions->isEmpty()) {
                    return response()->json(['message' => 'No approved mock questions are available in this batch.'], 422);
                }

                $questionIdsBySubject[$subjectIds[0]] = $batchQuestions->pluck('id')->map(fn ($id): int => (int) $id)->all();
                $questionsPerSubject[$subjectIds[0]] = $batchQuestions->count();
                foreach ($batchQuestions as $question) {
                    $optionIds = $question->options->pluck('id')->map(fn ($id): int => (int) $id)->all();
                    shuffle($optionIds);
                    $optionOrder[$question->id] = $optionIds;
                }
                $durationMinutes = 60;
            }

            foreach ($mockGroup ? [] : $subjectIds as $subjectId) {
                $subject = $subjects->get($subjectId);
                $specification = $this->mockGroupService->getSubjectMockSpecification($examType, $subject);
                $availableQuestionCount = Question::query()
                    ->where('exam_type_id', $examType->id)
                    ->where('subject_id', $subject->id)
                    ->where('is_mock', true)
                    ->where('is_active', true)
                    ->where('status', 'approved')
                    ->whereHas('options')
                    ->count();

                if ($availableQuestionCount < $specification['questions']) {
                    return response()->json([
                        'message' => "Not enough mock questions for {$subject->name}.",
                        'errors' => [
                            'subject_ids' => [
                                "Needed {$specification['questions']}, available {$availableQuestionCount}.",
                            ],
                        ],
                    ], 422);
                }

                $mockQuestions = Question::query()
                    ->where('exam_type_id', $examType->id)
                    ->where('subject_id', $subject->id)
                    ->where('is_mock', true)
                    ->where('is_active', true)
                    ->where('status', 'approved')
                    ->whereHas('options')
                    ->with('options')
                    ->inRandomOrder()
                    ->limit($specification['questions'])
                    ->get();

                $selectedQuestionIds = $mockQuestions
                    ->shuffle()
                    ->pluck('id')
                    ->map(fn ($id): int => (int) $id)
                    ->all();

                if (count($selectedQuestionIds) !== $specification['questions']) {
                    return response()->json([
                        'message' => "Not enough usable mock questions for {$subject->name}.",
                    ], 422);
                }

                $questionIdsBySubject[$subjectId] = $selectedQuestionIds;
                $questionsPerSubject[$subjectId] = count($selectedQuestionIds);

                foreach ($mockQuestions as $question) {
                    $optionIds = $question->options->pluck('id')->map(fn ($id): int => (int) $id)->all();
                    shuffle($optionIds);
                    $optionOrder[$question->id] = $optionIds;
                }

                $mockGroups = $this->mockGroupService->getMockGroups($subject, $examType);

                if ($mockGroups->isEmpty()) {
                    $this->mockGroupService->groupMockQuestions($subject, $examType);
                    $mockGroups = $this->mockGroupService->getMockGroups($subject, $examType);
                }

                $firstGroup = $this->mockGroupService->getFirstGroup($subject, $examType);
                $firstGroupData = $firstGroup ? (new MockGroupResource($firstGroup))->resolve() : null;

                $subjectsData[] = [
                    'id' => $subject->id,
                    'name' => $subject->name,
                    'code' => $subject->code,
                    'slug' => $subject->slug,
                    'icon' => $subject->icon,
                    'color' => $subject->color,
                    'total_groups' => $mockGroups->count(),
                    'first_group' => $firstGroupData,
                    'question_count' => $specification['questions'],
                    'time_limit_minutes' => $specification['time'],
                ];
            }

            if (! $mockGroup) {
                $durationMinutes = $this->mockGroupService->getFullMockDuration($examType, $subjects->values());
            } else {
                $subject = $subjects->get($subjectIds[0]);
                $subjectsData[] = [
                    'id' => $subject->id,
                    'name' => $subject->name,
                    'code' => $subject->code,
                    'slug' => $subject->slug,
                    'icon' => $subject->icon,
                    'color' => $subject->color,
                    'total_groups' => $this->mockGroupService->getMockGroups($subject, $examType)->count(),
                    'first_group' => null,
                    'question_count' => $questionsPerSubject[$subject->id],
                    'time_limit_minutes' => 60,
                ];
            }

            $questionOrder = array_merge(...array_values($questionIdsBySubject));
            $totalQuestions = count($questionOrder);
            $timeLimitPerSubject = intdiv($durationMinutes, $subjects->count());

            $sessionData = DB::transaction(function () use (
                $request,
                $examType,
                $subjectIds,
                $questionsPerSubject,
                $optionOrder,
                $mockGroup,
                $durationMinutes,
                $questionOrder,
                $totalQuestions,
                $subjectsData,
                $timeLimitPerSubject
            ): array {
                $session = MockSession::query()->create([
                    'user_id' => $request->user()->id,
                    'exam_type_id' => $examType->id,
                    'mock_group_id' => $mockGroup?->id,
                    'subject_ids' => $subjectIds,
                    'questions_per_subject' => $questionsPerSubject,
                    'option_order' => $optionOrder,
                    'time_limit' => $durationMinutes,
                    'selected_year' => null,
                    'shuffle' => true,
                    'status' => 'active',
                    'expires_at' => now()->addHours(24),
                ]);

                $attempt = QuizAttempt::query()->create([
                    'user_id' => $request->user()->id,
                    'exam_type_id' => $examType->id,
                    'subject_id' => $subjectIds[0] ?? null,
                    'mock_group_id' => $mockGroup?->id,
                    'exam_year' => null,
                    'score' => 0,
                    'total_questions' => $totalQuestions,
                    'correct_answers' => 0,
                    'percentage' => 0,
                    'score_percentage' => 0,
                    'time_taken_seconds' => 0,
                    'started_at' => now(),
                    'status' => 'in_progress',
                    'question_order' => $questionOrder,
                    'current_question_index' => 0,
                ]);

                $session->update(['quiz_attempt_id' => $attempt->id]);

                return [
                    'session_id' => $session->id,
                    'mode' => $mockGroup ? 'batch' : 'full',
                    'mock_group_id' => $mockGroup?->id,
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
            });

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

    public function activeSession(): JsonResponse
    {
        $session = MockSession::query()
            ->where('user_id', auth()->id())
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->latest('created_at')
            ->first();

        return response()->json([
            'message' => 'Active mock session retrieved successfully',
            'data' => $session ? [
                'session_id' => $session->id,
                'mode' => $session->mock_group_id ? 'batch' : 'full',
                'exam_type_id' => $session->exam_type_id,
                'subject_ids' => $session->subject_ids,
            ] : null,
        ]);
    }

    public function showSession(int $sessionId): JsonResponse
    {
        $session = $this->findOwnedSession($sessionId);
        if (! $session) {
            return response()->json(['message' => 'Mock session not found.'], 404);
        }

        if ($session->isExpired()) {
            $session->update(['status' => 'expired']);
            $session->quizAttempt?->update(['status' => 'expired']);

            return response()->json(['message' => 'Mock session expired. Start a new mock.'], 410);
        }

        if ($session->status === 'completed') {
            return response()->json(['data' => $this->completedSessionPayload($session)]);
        }

        $attempt = $session->quizAttempt;
        if (! $attempt || $attempt->status !== 'in_progress') {
            return response()->json(['message' => 'Mock session is unavailable.'], 409);
        }

        if ($this->remainingSeconds($session, $attempt) <= 0) {
            $this->completeSession($session);
            $session->refresh();

            return response()->json(['data' => $this->completedSessionPayload($session)]);
        }

        return response()->json(['data' => $this->activeSessionPayload($session)]);
    }

    public function saveProgress(MockSessionProgressRequest $request, int $sessionId): JsonResponse
    {
        $session = $this->findOwnedSession($sessionId);
        if (! $session) {
            return response()->json(['message' => 'Mock session not found.'], 404);
        }

        if ($session->status !== 'active' || $session->isExpired()) {
            return response()->json(['message' => 'Mock session is no longer active.'], 409);
        }

        $attempt = $session->quizAttempt;
        if (! $attempt || $attempt->status !== 'in_progress') {
            return response()->json(['message' => 'Mock session is unavailable.'], 409);
        }

        if ($this->remainingSeconds($session, $attempt) <= 0) {
            $this->completeSession($session);

            return response()->json(['message' => 'Mock time expired; answers have been submitted.'], 409);
        }

        $this->persistProgress($request, $session, $attempt);

        return response()->json([
            'message' => 'Mock progress saved.',
            'data' => [
                'current_question_index' => $attempt->fresh()->current_question_index,
                'remaining_seconds' => $this->remainingSeconds($session, $attempt),
            ],
        ]);
    }

    public function submitSession(MockSessionProgressRequest $request, int $sessionId): JsonResponse
    {
        $session = $this->findOwnedSession($sessionId);
        if (! $session) {
            return response()->json(['message' => 'Mock session not found.'], 404);
        }

        if ($session->status === 'completed') {
            return response()->json(['data' => $this->completedSessionPayload($session)]);
        }

        if ($session->status !== 'active' || $session->isExpired()) {
            return response()->json(['message' => 'Mock session is no longer active.'], 409);
        }

        $attempt = $session->quizAttempt;
        if (! $attempt || $attempt->status !== 'in_progress') {
            return response()->json(['message' => 'Mock session is unavailable.'], 409);
        }

        if ($this->remainingSeconds($session, $attempt) <= 0) {
            $this->completeSession($session);
            $session->refresh();

            return response()->json(['data' => $this->completedSessionPayload($session)]);
        }

        $this->persistProgress($request, $session, $attempt);
        $this->completeSession($session);
        $session->refresh();

        return response()->json(['data' => $this->completedSessionPayload($session)]);
    }

    private function findOwnedSession(int $sessionId): ?MockSession
    {
        return MockSession::query()
            ->with(['quizAttempt', 'examType'])
            ->where('user_id', auth()->id())
            ->find($sessionId);
    }

    private function persistProgress(
        MockSessionProgressRequest $request,
        MockSession $session,
        QuizAttempt $attempt
    ): void {
        $validated = $request->validated();
        $questionOrder = array_map('intval', $attempt->question_order ?? []);
        $answers = $validated['answers'] ?? [];

        if (array_key_exists('question_id', $validated)) {
            $answers[$validated['question_id']] = $validated['option_id'] ?? null;
        }

        foreach ($answers as $questionId => $optionId) {
            $questionId = (int) $questionId;
            if (! in_array($questionId, $questionOrder, true)) {
                throw ValidationException::withMessages([
                    'answers' => ['An answer references a question outside this mock session.'],
                ]);
            }

            if ($optionId !== null && ! Option::query()
                ->where('id', (int) $optionId)
                ->where('question_id', $questionId)
                ->exists()) {
                throw ValidationException::withMessages([
                    'answers' => ['A selected option does not belong to its question.'],
                ]);
            }
        }

        if (array_key_exists('current_question_index', $validated)
            && $validated['current_question_index'] >= count($questionOrder)) {
            throw ValidationException::withMessages([
                'current_question_index' => ['The question position is outside this mock session.'],
            ]);
        }

        DB::transaction(function () use ($request, $attempt, $answers, $validated): void {
            foreach ($answers as $questionId => $optionId) {
                UserAnswer::query()->updateOrCreate(
                    [
                        'quiz_attempt_id' => $attempt->id,
                        'question_id' => (int) $questionId,
                    ],
                    [
                        'user_id' => $request->user()->id,
                        'option_id' => $optionId,
                        'is_correct' => null,
                    ]
                );
            }

            if (array_key_exists('current_question_index', $validated)) {
                $attempt->update(['current_question_index' => $validated['current_question_index']]);
            }
        });
    }

    private function completeSession(MockSession $session): void
    {
        $attempt = $session->quizAttempt;
        if (! $attempt || $attempt->status !== 'in_progress') {
            return;
        }

        DB::transaction(function () use ($session, $attempt): void {
            $questionIds = array_map('intval', $attempt->question_order ?? []);
            $questions = Question::query()
                ->whereIn('id', $questionIds)
                ->with('options')
                ->get()
                ->keyBy('id');
            $answers = UserAnswer::query()
                ->where('quiz_attempt_id', $attempt->id)
                ->get()
                ->keyBy('question_id');
            $correctCount = 0;
            $answeredCount = 0;

            foreach ($questionIds as $questionId) {
                $question = $questions->get($questionId);
                if (! $question) {
                    continue;
                }

                $answer = $answers->get($questionId);
                $optionId = $answer?->option_id;
                $selectedOption = $optionId
                    ? $question->options->firstWhere('id', $optionId)
                    : null;
                $isCorrect = (bool) ($selectedOption?->is_correct ?? false);
                if ($optionId !== null) {
                    $answeredCount++;
                }
                if ($isCorrect) {
                    $correctCount++;
                }

                UserAnswer::query()->updateOrCreate(
                    [
                        'quiz_attempt_id' => $attempt->id,
                        'question_id' => $questionId,
                    ],
                    [
                        'user_id' => $attempt->user_id,
                        'option_id' => $optionId,
                        'is_correct' => $isCorrect,
                    ]
                );
            }

            $totalQuestions = count($questionIds);
            $percentage = $totalQuestions > 0 ? round(($correctCount / $totalQuestions) * 100, 2) : 0;
            $timeTaken = min($session->time_limit * 60, $this->elapsedSeconds($attempt));

            $attempt->update([
                'score' => $correctCount,
                'correct_answers' => $correctCount,
                'total_questions' => $totalQuestions,
                'percentage' => $percentage,
                'score_percentage' => $percentage,
                'time_taken_seconds' => $timeTaken,
                'completed_at' => now(),
                'status' => 'completed',
            ]);

            $session->update(['status' => 'completed']);
        });
    }

    private function activeSessionPayload(MockSession $session): array
    {
        $attempt = $session->quizAttempt;
        $questionsBySubject = $this->sessionQuestions($session, false);
        $questionIds = array_map('intval', $attempt->question_order ?? []);
        $answers = UserAnswer::query()
            ->where('quiz_attempt_id', $attempt->id)
            ->pluck('option_id', 'question_id')
            ->all();
        $subjects = Subject::query()->whereIn('id', $session->subject_ids)->get()->keyBy('id');
        $subjectData = [];

        foreach ($session->subject_ids as $subjectId) {
            $subject = $subjects->get($subjectId);
            $specification = $subject
                ? $this->mockGroupService->getSubjectMockSpecification($session->examType, $subject)
                : ['time' => null];

            $subjectData[] = [
                'id' => (int) $subjectId,
                'name' => $subject?->name ?? '',
                'question_count' => count($questionsBySubject[$subjectId] ?? []),
                'time_limit_minutes' => $session->mock_group_id ? 60 : $specification['time'],
            ];
        }

        return [
            'session_id' => $session->id,
            'mode' => $session->mock_group_id ? 'batch' : 'full',
            'mock_group_id' => $session->mock_group_id,
            'status' => $session->status,
            'exam_type' => [
                'id' => $session->examType->id,
                'name' => $session->examType->name,
                'slug' => $session->examType->slug,
            ],
            'subjects' => $subjectData,
            'questions_by_subject' => $questionsBySubject,
            'answers_by_question' => $answers,
            'current_question_index' => (int) ($attempt->current_question_index ?? 0),
            'duration_minutes' => $session->time_limit,
            'remaining_seconds' => $this->remainingSeconds($session, $attempt),
            'total_questions' => count($questionIds),
        ];
    }

    private function completedSessionPayload(MockSession $session): array
    {
        $attempt = $session->quizAttempt;
        $payload = $this->activeSessionPayload($session);
        $payload['status'] = 'completed';
        $payload['remaining_seconds'] = 0;
        $payload['questions_by_subject'] = $this->sessionQuestions($session, true);
        $payload['scores_by_subject'] = $this->scoresBySubject($session);
        $payload['score'] = (int) $attempt->score;
        $payload['total'] = (int) $attempt->total_questions;
        $payload['percentage'] = (float) $attempt->percentage;
        $payload['time_taken_seconds'] = (int) $attempt->time_taken_seconds;

        return $payload;
    }

    private function sessionQuestions(MockSession $session, bool $includeCorrectness): array
    {
        $attempt = $session->quizAttempt;
        $questionIds = array_map('intval', $attempt?->question_order ?? []);
        if ($questionIds === []) {
            return [];
        }

        $questions = Question::query()
            ->whereIn('id', $questionIds)
            ->with('options')
            ->get()
            ->keyBy('id');
        $result = [];

        foreach ($session->subject_ids as $subjectId) {
            $result[$subjectId] = [];

            foreach ($questionIds as $questionId) {
                $question = $questions->get($questionId);
                if (! $question || (int) $question->subject_id !== (int) $subjectId) {
                    continue;
                }

                $optionsById = $question->options->keyBy('id');
                $optionIds = $session->option_order[$questionId] ?? $question->options->pluck('id')->all();
                $options = [];

                foreach ($optionIds as $optionId) {
                    $option = $optionsById->get($optionId);
                    if (! $option) {
                        continue;
                    }

                    $optionData = [
                        'id' => $option->id,
                        'option_text' => $option->option_text,
                        'option_text_html' => $option->option_text_html,
                        'option_image' => $option->option_image,
                    ];
                    if ($includeCorrectness) {
                        $optionData['is_correct'] = (bool) $option->is_correct;
                    }
                    $options[] = $optionData;
                }

                $questionData = [
                    'id' => $question->id,
                    'question_text' => $question->question_text,
                    'question_text_html' => $question->question_text_html,
                    'question_image' => $question->question_image,
                    'options' => $options,
                ];
                if ($includeCorrectness) {
                    $questionData['explanation'] = $question->explanation;
                    $questionData['explanation_html'] = $question->explanation_html;
                }
                $result[$subjectId][] = $questionData;
            }
        }

        return $result;
    }

    private function scoresBySubject(MockSession $session): array
    {
        $answers = UserAnswer::query()
            ->where('quiz_attempt_id', $session->quiz_attempt_id)
            ->get()
            ->keyBy('question_id');
        $questionIds = array_map('intval', $session->quizAttempt->question_order ?? []);
        $questions = Question::query()->whereIn('id', $questionIds)->get()->keyBy('id');
        $scores = [];

        foreach ($session->subject_ids as $subjectId) {
            $subjectQuestions = $questions->where('subject_id', $subjectId);
            $scores[$subjectId] = [
                'score' => $subjectQuestions->filter(fn (Question $question): bool => (bool) $answers->get($question->id)?->is_correct)->count(),
                'total' => $subjectQuestions->count(),
            ];
        }

        return $scores;
    }

    private function elapsedSeconds(QuizAttempt $attempt): int
    {
        return max(0, (int) $attempt->started_at?->diffInSeconds(now()));
    }

    private function remainingSeconds(MockSession $session, QuizAttempt $attempt): int
    {
        return max(0, ($session->time_limit * 60) - $this->elapsedSeconds($attempt));
    }
}
