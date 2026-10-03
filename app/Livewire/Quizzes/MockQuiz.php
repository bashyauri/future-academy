<?php

namespace App\Livewire\Quizzes;

use App\Models\ExamType;
use App\Models\MockGroup;
use App\Models\MockSession;
use App\Models\Question;
use App\Models\Subject;
use App\Services\MockExamService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class MockQuiz extends Component
{
    public ?int $examTypeId = null;

    public ?int $selectedYear = null;

    public array $subjectIds = [];

    public $subjectsData = [];

    public array $questionsBySubject = [];

    public array $questions = [];

    public array $allQuestionIds = [];

    public array $flatAnswers = [];

    public int $currentSubjectIndex = 0;

    public int $currentQuestionIndex = 0;

    public array $userAnswers = [];

    public bool $showResults = false;

    public bool $showReview = false;

    public ?int $quizAttemptId = null;

    public int $timeRemaining = 0;

    public int $timeLimit = 180;

    public array $questionsPerSubject = [];

    public bool $showAnswersImmediately = false;

    public bool $showExplanations = false;

    public bool $shuffleQuestions = true;

    public ?int $currentMockGroupId = null;

    public ?MockGroup $currentMockGroup = null;

    /** ISO-8601 absolute server expiry used by Alpine for display only. */
    public ?string $expiresAt = null;

    protected ?MockSession $mockSession = null;

    public function mount()
    {
        $groupId = request()->query('group');

        if ($groupId) {
            return $this->loadFromMockGroup((int) $groupId);
        }

        $sessionId = request()->query('session');

        if (! $sessionId) {
            return $this->redirectToSetup();
        }

        $session = MockSession::whereKey($sessionId)
            ->where('user_id', auth()->id())
            ->where('status', 'active')
            ->first();

        if (! $session) {
            session()->flash('error', 'Mock session expired or invalid. Please start a new mock.');

            return $this->redirectToSetup();
        }

        $this->mockSession = $session;
        $this->loadSessionConfiguration($session);

        if (! $this->isSessionStillValid()) {
            session()->flash('error', 'This mock session has expired. Please start a new mock.');

            return $this->redirectToSetup();
        }

        if ($response = $this->loadSubjectsAndQuestions()) {
            return $response;
        }

        $this->syncTimeRemaining();
    }

    protected function loadSessionConfiguration(MockSession $session): void
    {
        $this->examTypeId = $session->exam_type_id;
        $this->subjectIds = array_values(array_map('intval', $session->subject_ids ?? []));
        $this->questionsPerSubject = $session->questions_per_subject ?? [];
        $this->timeLimit = (int) $session->time_limit;
        $this->selectedYear = $session->selected_year;
        $this->shuffleQuestions = (bool) $session->shuffle;

        if (! $this->examTypeId || empty($this->subjectIds)) {
            return;
        }

        if (! ExamType::whereKey($this->examTypeId)->where('is_active', true)->exists()) {
            $this->subjectIds = [];

            return;
        }

        // The 24-hour MockSession::expires_at is only the session validity window.
        // The actual exam timer must use QuizAttempt::started_at + time_limit.
        $attempt = $session->quizAttempt;

        if (! $attempt) {
            $this->expiresAt = now()->addMinutes($this->timeLimit)->toIso8601String();

            return;
        }

        $startedAt = $attempt->started_at ?? $session->created_at ?? now();
        $this->expiresAt = $startedAt
            ->copy()
            ->addMinutes($this->timeLimit)
            ->toIso8601String();
    }

    protected function loadFromMockGroup(int $groupId)
    {
        if ($groupId <= 0) {
            session()->flash('error', 'Invalid mock group.');

            return redirect()->route('mock.setup');
        }

        try {
            $mockGroup = MockGroup::with('subject', 'examType')
                ->whereKey($groupId)
                ->firstOrFail();

            $activeQuestions = $mockGroup->questions()
                ->where('is_active', true)
                ->where('status', 'approved')
                ->count();

            if ($activeQuestions === 0) {
                session()->flash('error', 'No active questions available in this mock.');

                return redirect()->route('mock.setup');
            }

            $this->currentMockGroupId = $mockGroup->id;
            $this->currentMockGroup = $mockGroup;
            $this->examTypeId = $mockGroup->exam_type_id;
            $this->subjectIds = [(int) $mockGroup->subject_id];
            $this->questionsPerSubject = [
                $mockGroup->subject_id => (int) $mockGroup->total_questions,
            ];
            $this->timeLimit = 60;
            $this->shuffleQuestions = true;

            $this->expiresAt = now()->addMinutes($this->timeLimit)->toIso8601String();

            if ($response = $this->loadSubjectsAndQuestions()) {
                return $response;
            }

            $this->syncTimeRemaining();
        } catch (ModelNotFoundException) {
            session()->flash('error', 'Mock group not found.');

            return redirect()->route('mock.setup');
        } catch (\Throwable $e) {
            report($e);
            session()->flash('error', 'An error occurred. Please try again.');

            return redirect()->route('mock.setup');
        }
    }

    protected function redirectToSetup()
    {
        return redirect()->route('mock.setup');
    }

    protected function loadSubjectsAndQuestions()
    {
        $this->subjectsData = Subject::whereIn('id', $this->subjectIds)
            ->get()
            ->sortBy(fn ($subject) => array_search($subject->id, $this->subjectIds))
            ->values();

        $this->subjectIds = $this->subjectsData->pluck('id')->map(fn ($id) => (int) $id)->all();

        if (empty($this->subjectIds)) {
            session()->flash('error', 'No valid subjects were found.');

            return $this->redirectToSetup();
        }

        // Session flow is authoritative: use the exact question and option order
        // generated by MockExamController::initializeSession().
        if ($this->mockSession) {
            $attempt = $this->mockSession->quizAttempt;
            $questionIds = array_map('intval', $attempt?->question_order ?? []);

            if ($questionIds === []) {
                session()->flash('error', 'This mock session has no question order. Please start a new mock.');

                return $this->redirectToSetup();
            }

            $questions = Question::query()
                ->whereIn('id', $questionIds)
                ->with('options')
                ->get()
                ->keyBy('id');

            foreach ($this->subjectIds as $subjectId) {
                $this->questionsBySubject[$subjectId] = [];

                foreach ($questionIds as $questionId) {
                    $question = $questions->get($questionId);

                    if (! $question || (int) $question->subject_id !== (int) $subjectId) {
                        continue;
                    }

                    $optionIds = $this->mockSession->option_order[$questionId]
                        ?? $question->options->pluck('id')->all();
                    $optionsById = $question->options->keyBy('id');
                    $orderedOptions = collect($optionIds)
                        ->map(fn ($optionId) => $optionsById->get((int) $optionId))
                        ->filter()
                        ->values();

                    $question->setRelation('options', $orderedOptions);
                    $this->questionsBySubject[$subjectId][] = $question;
                }

                $this->userAnswers[$subjectId] = array_fill(
                    0,
                    count($this->questionsBySubject[$subjectId]),
                    null
                );
            }

            $this->rebuildFlatAnswerState();
            $this->clampPosition();

            return null;
        }

        foreach ($this->subjectIds as $subjectId) {
            $questionCount = (int) ($this->questionsPerSubject[$subjectId] ?? 40);
            $questions = $this->currentMockGroup->questions()
                ->where('is_active', true)
                ->where('status', 'approved')
                ->with('options')
                ->get();

            if ($questions->isEmpty()) {
                session()->flash('error', 'No questions available in this mock group.');

                return $this->redirectToSetup();
            }

            if ($this->shuffleQuestions) {
                $questions = $questions->shuffle()->map(function ($question) {
                    $question->setRelation('options', $question->options->shuffle()->values());

                    return $question;
                });
            }

            $questions = $questions->take($questionCount)->values();
            $this->questionsBySubject[$subjectId] = $questions;
            $this->userAnswers[$subjectId] = array_fill(0, $questions->count(), null);
        }

        $this->rebuildFlatQuestionState();
        $this->rebuildFlatAnswerState();

        return null;
    }

    protected function rebuildFlatQuestionState(): void
    {
        $this->questions = [];

        foreach ($this->subjectIds as $subjectId) {
            foreach ($this->questionsBySubject[$subjectId] ?? [] as $question) {
                $this->questions[] = $question;
            }
        }

        $this->allQuestionIds = array_values(array_map(fn ($question) => (int) $question->id, $this->questions));
    }

    protected function rebuildFlatAnswerState(): void
    {
        $this->flatAnswers = [];

        foreach ($this->questions as $question) {
            $this->flatAnswers[(int) $question->id] = null;
        }

        foreach ($this->questionsBySubject as $subjectId => $questions) {
            foreach ($questions as $index => $question) {
                $value = $this->userAnswers[$subjectId][$index] ?? null;
                $this->flatAnswers[(int) $question->id] = $value === null ? null : (int) $value;
            }
        }
    }


    protected function clampPosition(): void
    {
        if (empty($this->subjectsData)) {
            $this->currentSubjectIndex = 0;
            $this->currentQuestionIndex = 0;

            return;
        }

        $this->currentSubjectIndex = max(
            0,
            min($this->currentSubjectIndex, $this->subjectsData->count() - 1)
        );

        $subjectId = $this->getCurrentSubjectId();
        $questions = $this->questionsBySubject[$subjectId] ?? [];

        $this->currentQuestionIndex = $questions
            ? max(0, min($this->currentQuestionIndex, count($questions) - 1))
            : 0;
    }

    protected function isSessionStillValid(): bool
    {
        if (! $this->expiresAt) {
            return true;
        }

        return now()->lt(Carbon::parse($this->expiresAt));
    }

    protected function syncTimeRemaining(): void
    {
        if ($this->expiresAt) {
            $this->timeRemaining = max(
                0,
                now()->diffInSeconds(Carbon::parse($this->expiresAt), false)
            );
        }
    }

    public function getCurrentSubjectId(): ?int
    {
        return $this->subjectsData[$this->currentSubjectIndex]->id ?? null;
    }

    public function getCurrentQuestions()
    {
        if (! empty($this->questions)) {
            return $this->questions;
        }

        $subjectId = $this->getCurrentSubjectId();

        return $subjectId ? ($this->questionsBySubject[$subjectId] ?? []) : [];
    }

    public function getCurrentQuestion()
    {
        $questions = $this->getCurrentQuestions();

        return $questions[$this->currentQuestionIndex] ?? null;
    }

    protected function ensureExamIsActive(): void
    {
        if ($this->showResults) {
            return;
        }

        $this->syncTimeRemaining();

        if ($this->timeRemaining <= 0) {
            $this->submitQuiz();
            abort(409, 'Exam time has expired.');
        }
    }

    public function selectAnswer(int $optionId): void
    {
        $this->ensureExamIsActive();

        $subjectId = $this->getCurrentSubjectId();
        $question = $this->getCurrentQuestion();

        if (! $subjectId || ! $question) {
            return;
        }

        $validOption = $question->options->contains(fn ($option) => (int) $option->id === $optionId);

        if (! $validOption) {
            return;
        }

        if (! isset($this->userAnswers[$subjectId])) {
            $this->userAnswers[$subjectId] = [];
        }

        $this->userAnswers[$subjectId][$this->currentQuestionIndex] = $optionId;
        $this->flatAnswers[(int) $question->id] = (int) $optionId;

    }

    public function switchSubject(int $index): void
    {
        $this->ensureExamIsActive();

        if (! isset($this->subjectsData[$index])) {
            return;
        }

        $this->currentSubjectIndex = $index;
        $this->currentQuestionIndex = 0;

    }

    public function nextQuestion(): void
    {
        $this->ensureExamIsActive();

        $questions = $this->getCurrentQuestions();
        $maxIndex = count($questions) - 1;

        if ($maxIndex < 0) {
            return;
        }

        if ($this->currentQuestionIndex < $maxIndex) {
            $this->currentQuestionIndex++;
        } elseif ($this->currentSubjectIndex < $this->subjectsData->count() - 1) {
            $this->currentSubjectIndex++;
            $this->currentQuestionIndex = 0;
        }

    }

    public function previousQuestion(): void
    {
        $this->ensureExamIsActive();

        if ($this->currentQuestionIndex > 0) {
            $this->currentQuestionIndex--;
        } elseif ($this->currentSubjectIndex > 0) {
            $this->currentSubjectIndex--;

            $previousSubjectId = $this->getCurrentSubjectId();
            $this->currentQuestionIndex = max(
                count($this->questionsBySubject[$previousSubjectId] ?? []) - 1,
                0
            );
        }

    }

    public function jumpToQuestion(int $subjectIndex, int $questionIndex): void
    {
        $this->ensureExamIsActive();

        if (! isset($this->subjectsData[$subjectIndex])) {
            return;
        }

        $subjectId = $this->subjectsData[$subjectIndex]->id;
        $questions = $this->questionsBySubject[$subjectId] ?? [];

        if (! isset($questions[$questionIndex])) {
            return;
        }

        $this->currentSubjectIndex = $subjectIndex;
        $this->currentQuestionIndex = $questionIndex;

    }

    public function handleTimerEnd(): void
    {
        $this->syncTimeRemaining();

        if ($this->timeRemaining <= 0) {
            $this->submitQuiz();
        }
    }

    public function submitQuiz(): void
    {
        if ($this->showResults) {
            return;
        }

        $this->showResults = true;

        $this->saveAttempt();
    }

    protected function saveAttempt(): void
    {
        $sessionId = request()->query('session');

        if (! $sessionId) {
            // Legacy group flow has no MockSession to complete.
            return;
        }

        $session = MockSession::query()
            ->with('quizAttempt')
            ->whereKey($sessionId)
            ->where('user_id', auth()->id())
            ->first();

        if (! $session || ! $session->quizAttempt) {
            return;
        }

        $answers = [];

        foreach ($this->subjectIds as $subjectId) {
            foreach (($this->questionsBySubject[$subjectId] ?? []) as $index => $question) {
                $answers[(int) $question->id] = $this->userAnswers[$subjectId][$index] ?? null;
            }
        }

        /** @var MockExamService $service */
        $service = app(MockExamService::class);
        $attempt = $service->complete($session, $answers);

        $this->quizAttemptId = $attempt->id;
    }

    public function getScoresBySubject(): array
    {
        $scores = [];

        foreach ($this->subjectIds as $subjectId) {
            $score = 0;

            foreach (($this->questionsBySubject[$subjectId] ?? []) as $index => $question) {
                $userAnswer = $this->userAnswers[$subjectId][$index] ?? null;

                if ($userAnswer !== null) {
                    $correctOption = $question->options->firstWhere('is_correct', true);

                    if ($correctOption && (int) $correctOption->id === (int) $userAnswer) {
                        $score++;
                    }
                }
            }

            $scores[$subjectId] = $score;
        }

        return $scores;
    }

    public function toggleReview(): void
    {
        $this->showReview = ! $this->showReview;
    }

    public function getReviewData(): array
    {
        $reviewData = [];

        foreach ($this->subjectIds as $subjectId) {
            $subject = $this->subjectsData->firstWhere('id', $subjectId);
            $questions = $this->questionsBySubject[$subjectId] ?? [];
            $questionsWithAnswers = [];

            foreach ($questions as $index => $question) {
                $userAnswerId = $this->userAnswers[$subjectId][$index] ?? null;
                $correctOption = $question->options->firstWhere('is_correct', true);
                $userOption = $question->options->firstWhere('id', $userAnswerId);

                $questionsWithAnswers[] = [
                    'question' => $question,
                    'questionNumber' => $index + 1,
                    'userAnswerId' => $userAnswerId,
                    'userOption' => $userOption,
                    'correctOption' => $correctOption,
                    'isCorrect' => $correctOption && (int) $correctOption->id === (int) $userAnswerId,
                    'wasAnswered' => $userAnswerId !== null,
                ];
            }

            $reviewData[] = [
                'subject' => $subject,
                'questions' => $questionsWithAnswers,
            ];
        }

        return $reviewData;
    }

    public function render()
    {
        $this->syncTimeRemaining();

        return view('livewire.quizzes.mock-quiz');
    }
}
