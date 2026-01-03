<?php

namespace App\Livewire\Quizzes;

use App\Models\ExamType;
use App\Models\MockSession;
use App\Models\Question;
use App\Models\QuizAttempt;
use App\Models\Subject;
use App\Models\UserAnswer;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

#[Layout('components.layouts.app')]
class MockQuiz extends Component
{
    public ?int $examTypeId = null;
    public ?int $selectedYear = null;
    public array $subjectIds = [];
    public $subjectsData = [];
    public array $questionsBySubject = [];

    public int $currentSubjectIndex = 0;
    public int $currentQuestionIndex = 0;
    public array $userAnswers = [];

    public bool $showResults = false;
    public bool $showReview = false;
    public ?int $quizAttemptId = null;

    public int $timeRemaining = 0; // seconds
    public int $timeLimit = 180; // minutes
    public array $questionsPerSubject = []; // Per-subject question counts
    public bool $showAnswersImmediately = false;
    public bool $showExplanations = false;
    public bool $shuffleQuestions = true;

    public function mount()
    {
        $sessionId = request()->query('session');

        if (!$sessionId) {
            return $this->redirectToSetup();
        }

        // Load session from database (secure, tamper-proof)
        $session = MockSession::where('id', $sessionId)
            ->where('user_id', auth()->id())
            ->where('status', 'active')
            ->first();

        if (!$session || $session->isExpired()) {
            session()->flash('error', 'Mock session expired or invalid. Please start a new mock.');
            return $this->redirectToSetup();
        }

        // Load configuration from secure session
        $this->examTypeId = $session->exam_type_id;
        $this->subjectIds = $session->subject_ids;
        $this->questionsPerSubject = $session->questions_per_subject;
        $this->timeLimit = $session->time_limit;
        $this->selectedYear = $session->selected_year;
        $this->shuffleQuestions = $session->shuffle;

        if (empty($this->examTypeId) || empty($this->subjectIds)) {
            return $this->redirectToSetup();
        }

        if (!ExamType::where('id', $this->examTypeId)->where('is_active', true)->exists()) {
            return $this->redirectToSetup();
        }

        $this->timeRemaining = $this->timeLimit * 60;

        if ($response = $this->loadSubjectsAndQuestions()) {
            return $response;
        }

        // Load previous answers if user refreshed or came back
        $this->loadPreviousAnswers($sessionId);
    }

    protected function redirectToSetup()
    {
        return redirect()->route('mock.setup');
    }

    protected function loadSubjectsAndQuestions()
    {
        $this->subjectsData = Subject::whereIn('id', $this->subjectIds)->get();
        $this->subjectIds = $this->subjectsData->pluck('id')->toArray();

        // Cache key unique to this quiz session
        $sessionId = request()->query('session');
        $cacheKey = "mock_quiz_{$sessionId}";

        // Try to load everything from unified cache (single Redis hit)
        $cachedData = cache()->get($cacheKey);

        if ($cachedData) {
            // Load from cache - instant!
            $this->questionsBySubject = $cachedData['questions'];
            $this->userAnswers = $cachedData['answers'];
            $this->currentSubjectIndex = $cachedData['position']['subjectIndex'] ?? 0;
            $this->currentQuestionIndex = $cachedData['position']['questionIndex'] ?? 0;
            return;
        }

        // First time - fetch all questions from database
        foreach ($this->subjectIds as $subjectId) {
            // Get question count for this specific subject (or default to 40)
            $questionCount = $this->questionsPerSubject[$subjectId] ?? 40;

            // Only pull mock questions for this mock quiz flow
            $query = Question::where('exam_type_id', $this->examTypeId)
                ->where('subject_id', $subjectId)
                ->where('is_mock', true)
                ->when($this->selectedYear, fn($q) => $q->where('exam_year', $this->selectedYear))
                ->where('is_active', true)
                ->where('status', 'approved')
                ->with('options');

            // Fetch more questions than needed for better randomization
            $questions = $query->get();

            if ($questions->count() === 0) {
                // Log for debugging
                \Log::warning('No mock questions found', [
                    'exam_type_id' => $this->examTypeId,
                    'subject_id' => $subjectId,
                    'year' => $this->selectedYear,
                    'questions_requested' => $questionCount,
                ]);

                // Keep user informed before sending back to setup
                session()->flash('error', 'No mock questions are available for the selected subjects. Only mock exams are supported. Please try another subject combination.');
                $this->addError('subjects', 'No mock questions available for one of the selected subjects.');
                return $this->redirectToSetup();
            }

            // Shuffle at collection level for true per-user randomization
            if ($this->shuffleQuestions) {
                $questions = $questions->shuffle();
                // Also shuffle the answer options for each question
                $questions = $questions->map(function ($question) {
                    $question->options = $question->options->shuffle();
                    return $question;
                });
            }

            // Take only the requested number after shuffling
            $questions = $questions->take($questionCount);

            $this->questionsBySubject[$subjectId] = $questions;
            $this->userAnswers[$subjectId] = array_fill(0, $questions->count(), null);
        }

        // Cache all data for this session (unified - 3 hours = max quiz time + buffer)
        cache()->put($cacheKey, [
            'questions' => $this->questionsBySubject,
            'answers' => $this->userAnswers,
            'position' => [
                'subjectIndex' => $this->currentSubjectIndex,
                'questionIndex' => $this->currentQuestionIndex,
            ],
        ], now()->addHours(3));
    }

    protected function loadPreviousAnswers(int $sessionId): void
    {
        // Load all cached data from unified cache key (single Redis operation)
        $cacheKey = "mock_quiz_{$sessionId}";
        $cached = cache()->get($cacheKey);

        if ($cached) {
            $this->userAnswers = $cached['answers'];
            $this->currentSubjectIndex = $cached['position']['subjectIndex'] ?? 0;
            $this->currentQuestionIndex = $cached['position']['questionIndex'] ?? 0;
        }
    }

    #[On('timer-ended')]
    public function handleTimerEnd(): void
    {
        $this->submitQuiz();
    }

    public function getCurrentSubjectId()
    {
        return $this->subjectsData[$this->currentSubjectIndex]->id;
    }

    public function getCurrentQuestions()
    {
        return $this->questionsBySubject[$this->getCurrentSubjectId()];
    }

    public function getCurrentQuestion()
    {
        return $this->getCurrentQuestions()[$this->currentQuestionIndex];
    }

    public function switchSubject(int $index): void
    {
        $this->currentSubjectIndex = $index;
        $this->currentQuestionIndex = 0;
    }

    public function selectAnswer(int $optionId): void
    {
        $currentSubjectId = $this->getCurrentSubjectId();
        $this->userAnswers[$currentSubjectId][$this->currentQuestionIndex] = $optionId;

        // Update unified cache with single operation
        $sessionId = request()->query('session');
        cache()->put("mock_quiz_{$sessionId}", [
            'questions' => $this->questionsBySubject,
            'answers' => $this->userAnswers,
            'position' => [
                'subjectIndex' => $this->currentSubjectIndex,
                'questionIndex' => $this->currentQuestionIndex,
            ],
        ], now()->addHours(3));
    }

    public function nextQuestion(): void
    {
        $currentSubjectId = $this->getCurrentSubjectId();
        $maxIndex = count($this->questionsBySubject[$currentSubjectId]) - 1;

        if ($this->currentQuestionIndex < $maxIndex) {
            $this->currentQuestionIndex++;
        } elseif ($this->currentSubjectIndex < count($this->subjectIds) - 1) {
            $this->currentSubjectIndex++;
            $this->currentQuestionIndex = 0;
        }

        // Update unified cache (single operation)
        $sessionId = request()->query('session');
        cache()->put("mock_quiz_{$sessionId}", [
            'questions' => $this->questionsBySubject,
            'answers' => $this->userAnswers,
            'position' => [
                'subjectIndex' => $this->currentSubjectIndex,
                'questionIndex' => $this->currentQuestionIndex,
            ],
        ], now()->addHours(3));
    }

    public function previousQuestion(): void
    {
        if ($this->currentQuestionIndex > 0) {
            $this->currentQuestionIndex--;
        } elseif ($this->currentSubjectIndex > 0) {
            $this->currentSubjectIndex--;
            $prevSubjectId = $this->getCurrentSubjectId();
            $this->currentQuestionIndex = max(count($this->questionsBySubject[$prevSubjectId]) - 1, 0);
        }

        // Update unified cache (single operation)
        $sessionId = request()->query('session');
        cache()->put("mock_quiz_{$sessionId}", [
            'questions' => $this->questionsBySubject,
            'answers' => $this->userAnswers,
            'position' => [
                'subjectIndex' => $this->currentSubjectIndex,
                'questionIndex' => $this->currentQuestionIndex,
            ],
        ], now()->addHours(3));
    }

    public function jumpToQuestion(int $subjectIndex, int $questionIndex): void
    {
        $this->currentSubjectIndex = $subjectIndex;
        $this->currentQuestionIndex = $questionIndex;

        // Update unified cache (single operation)
        $sessionId = request()->query('session');
        cache()->put("mock_quiz_{$sessionId}", [
            'questions' => $this->questionsBySubject,
            'answers' => $this->userAnswers,
            'position' => [
                'subjectIndex' => $this->currentSubjectIndex,
                'questionIndex' => $this->currentQuestionIndex,
            ],
        ], now()->addHours(3));
    }

    public function submitQuiz(): void
    {
        $this->showResults = true;
        $this->saveAttempt();
    }

    protected function saveAttempt(): void
    {
        $totalQuestions = 0;
        $totalScore = 0;
        $answeredCount = 0;
        $sessionId = request()->query('session');

        foreach ($this->subjectIds as $subjectId) {
            $totalQuestions += count($this->questionsBySubject[$subjectId]);
        }

        $attempt = QuizAttempt::create([
            'user_id' => auth()->id(),
            'exam_type_id' => $this->examTypeId,
            'exam_year' => $this->selectedYear,
            'score' => 0,
            'total_questions' => $totalQuestions,
            'time_taken_seconds' => ($this->timeLimit * 60) - $this->timeRemaining,
            'percentage' => 0,
            'started_at' => now()->subSeconds(($this->timeLimit * 60) - $this->timeRemaining),
            'completed_at' => now(),
            'answered_questions' => 0,
            'correct_answers' => 0,
            'score_percentage' => 0,
            'status' => 'completed',
        ]);

        // Batch save all answers to database at once
        foreach ($this->subjectIds as $subjectId) {
            foreach ($this->questionsBySubject[$subjectId] as $index => $question) {
                $userAnswer = $this->userAnswers[$subjectId][$index] ?? null;
                if ($userAnswer) {
                    $answeredCount++;
                }

                $correctOption = $question->options->firstWhere('is_correct', true);
                $isCorrect = $correctOption && $correctOption->id == $userAnswer;

                if ($isCorrect) {
                    $totalScore++;
                }

                UserAnswer::create([
                    'user_id' => auth()->id(),
                    'quiz_attempt_id' => $attempt->id,
                    'mock_session_id' => $sessionId,
                    'question_id' => $question->id,
                    'option_id' => $userAnswer,
                    'is_correct' => $isCorrect,
                ]);
            }
        }

        $percentage = $totalQuestions > 0 ? ($totalScore / $totalQuestions) * 100 : 0;

        $attempt->update([
            'score' => $totalScore,
            'percentage' => $percentage,
            'answered_questions' => $answeredCount,
            'correct_answers' => $totalScore,
            'score_percentage' => $percentage,
            'time_spent_seconds' => ($this->timeLimit * 60) - $this->timeRemaining,
            'completed_at' => now(),
        ]);

        $this->quizAttemptId = $attempt->id;

        // Clear unified cache after successful submit (single operation)
        cache()->forget("mock_quiz_{$sessionId}");
    }

    public function getScoresBySubject(): array
    {
        $scores = [];
        foreach ($this->subjectIds as $subjectId) {
            $score = 0;
            foreach ($this->questionsBySubject[$subjectId] as $index => $question) {
                $userAnswer = $this->userAnswers[$subjectId][$index] ?? null;
                if ($userAnswer) {
                    $correctOption = $question->options->firstWhere('is_correct', true);
                    if ($correctOption && $correctOption->id == $userAnswer) {
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
        $this->showReview = !$this->showReview;
    }

    public function getReviewData(): array
    {
        $reviewData = [];

        foreach ($this->subjectIds as $subjectId) {
            $subject = $this->subjectsData->firstWhere('id', $subjectId);
            $questions = $this->questionsBySubject[$subjectId];

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
                    'isCorrect' => $correctOption && $correctOption->id == $userAnswerId,
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
        return view('livewire.quizzes.mock-quiz');
    }
}
