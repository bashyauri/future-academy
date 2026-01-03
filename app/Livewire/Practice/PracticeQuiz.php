<?php

namespace App\Livewire\Practice;

use App\Models\ExamType;
use App\Models\Question;
use App\Models\QuizAttempt;
use App\Models\Subject;
use App\Models\UserAnswer;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Attributes\Computed;
use Livewire\Component;

#[Layout('components.layouts.app')]
class PracticeQuiz extends Component
{
    /**
     * Exit the quiz and allow user to continue later (without submitting).
     */
    public function exitQuiz()
    {
        // Persist current question index if authenticated and in-progress
        if (auth()->check() && $this->quizAttempt) {
            // Security: Verify user owns this attempt
            if ($this->quizAttempt->user_id !== auth()->id()) {
                abort(403, 'Unauthorized');
            }

            if ($this->quizAttempt->status === 'in_progress') {
                $this->flushPendingAnswers(); // Flush any pending answers first
                $this->persistCurrentQuestionIndex();
            }
        }
        // Redirect to practice home (or any other page as needed)
        return redirect()->route('practice.home');
    }
    #[Url]
    #[Locked]
    public $exam_type;

    #[Url]
    #[Locked]
    public $subject;

    #[Url]
    #[Locked]
    public $year;

    #[Url]
    public $shuffle = 0;

    #[Url]
    public $limit = null;

    #[Url]
    public $time = null;

    #[Url]
    public $results = false;

    #[Url]
    public $attempt = null;

    public $questions = [];
    public $currentQuestionIndex = 0;
    public $userAnswers = [];
    public $selectedAnswers = [];
    public $timeStarted;
    public $timeRemaining;
    public $showResults = false;
    public $score = 0;
    public $totalQuestions = 0;
    #[Locked]
    public ?QuizAttempt $quizAttempt = null;
    #[Locked]
    public array $questionIds = [];
    public bool $positionCacheDebounce = false;
    public string $lastSavedTime = '';
    private float $lastAnswerTime = 0;
    private array $pendingAnswers = []; // Batch answers before persist

    #[Computed]
    public function currentQuestion()
    {
        return $this->questions[$this->currentQuestionIndex] ?? null;
    }

    #[Computed]
    public function currentAnswerId()
    {
        return $this->userAnswers[$this->currentQuestionIndex] ?? null;
    }

    public function mount()
    {
        $this->showResults = false;

        // Validate required parameters
        if (!$this->subject) {
            session()->flash('error', 'Invalid subject. Please select a subject to practice.');
            return redirect()->route('practice.home');
        }

        // Validate subject exists and is active
        $subject = Subject::where('id', $this->subject)->where('is_active', true)->first();
        if (!$subject) {
            session()->flash('error', 'The selected subject is not available.');
            return redirect()->route('practice.home');
        }

        // Validate exam_type if provided
        if ($this->exam_type) {
            $examType = ExamType::where('id', $this->exam_type)
                ->where('is_active', true)
                ->first();
            if (!$examType) {
                session()->flash('error', 'The selected exam type is not available.');
                return redirect()->route('practice.home');
            }
        }

        // For authenticated users, try to restore or create a persistent attempt
        if (auth()->check()) {
            $attemptFromQuery = $this->attempt ? QuizAttempt::find($this->attempt) : null;

            // Verify ownership and validity of any provided attempt
            if ($attemptFromQuery && $attemptFromQuery->user_id !== auth()->id()) {
                abort(403, 'Unauthorized attempt access');
            }

            // If showing results, use the provided attempt (completed or in_progress)
            if ($this->results && $attemptFromQuery) {
                $this->quizAttempt = $attemptFromQuery;
                $this->hydrateFromAttempt($attemptFromQuery);
                $this->showResults = true;
                return;
            }
            // Otherwise, find existing in_progress attempt
            if ($attemptFromQuery && $attemptFromQuery->status === 'in_progress') {
                $this->quizAttempt = $attemptFromQuery;
                $this->hydrateFromAttempt($attemptFromQuery);
                $this->showResults = false;
                return;
            }
            $activeAttempt = $this->findActiveAttempt();
            if ($activeAttempt) {
                $this->quizAttempt = $activeAttempt;
                $this->hydrateFromAttempt($activeAttempt);
                $this->showResults = false;
                return;
            }
            // If attempt is completed, show results
            if ($attemptFromQuery && $attemptFromQuery->status === 'completed') {
                $this->quizAttempt = $attemptFromQuery;
                $this->hydrateFromAttempt($attemptFromQuery);
                $this->showResults = true;
                return;
            }
            // Only start new attempt if not showing results
            if (!$this->showResults) {
                $this->startNewAttempt();
            } else {
                return redirect()->route('practice.home');
            }
        } else {
            // Guest: fresh session
            $this->loadQuestions();
        }
    }

    private function findActiveAttempt(): ?QuizAttempt
    {
        return QuizAttempt::where('user_id', auth()->id())
            ->where('exam_type_id', $this->exam_type)
            ->where('exam_year', $this->year)
            ->where('status', 'in_progress')
            ->latest('created_at')
            ->first();
    }

    private function hydrateFromAttempt(QuizAttempt $attempt): void
    {
        $this->quizAttempt = $attempt;
        $this->attempt = $attempt->id;
        // Restore current question index from DB
        $this->currentQuestionIndex = $attempt->current_question_index ?? 0;

        // Flatten question_order if it's nested (from JAMB-style storage)
        $questionOrder = $attempt->question_order ?? [];
        if (is_array($questionOrder) && !empty($questionOrder)) {
            // Check if it's nested (has array values)
            $firstValue = reset($questionOrder);
            if (is_array($firstValue)) {
                // Flatten nested arrays
                $this->questionIds = array_merge(...array_values($questionOrder));
            } else {
                // Already flat
                $this->questionIds = $questionOrder;
            }
        } else {
            $this->questionIds = [];
        }

        $this->timeStarted = $attempt->started_at;
        $this->timeRemaining = $this->computeRemainingTime();

        // Try to load from unified cache first
        $cacheKey = "practice_attempt_{$attempt->id}";
        $cached = cache()->get($cacheKey);

        if ($cached) {
            // Restore from cache
            $this->questions = $cached['questions'];
            $this->userAnswers = $cached['answers'];
            $this->currentQuestionIndex = $cached['position'];
            $this->totalQuestions = count($this->questions);
        } else {
            // Load questions in the order stored
            if (!empty($this->questionIds)) {
                $this->questions = Question::whereIn('id', $this->questionIds)
                    ->with('options:id,question_id,option_text,option_image,is_correct')
                    ->select('id', 'question_text', 'question_image', 'difficulty', 'explanation')
                    ->get()
                    ->sortBy(function ($q) {
                        return array_search($q->id, $this->questionIds);
                    })
                    ->map(function ($question) {
                        return [
                            'id' => $question->id,
                            'question_text' => $question->question_text,
                            'question_image' => $question->question_image,
                            'explanation' => $question->explanation,
                            'options' => $question->options->map(function ($option) {
                                return [
                                    'id' => $option->id,
                                    'option_text' => $option->option_text,
                                    'option_image' => $option->option_image,
                                    'is_correct' => $option->is_correct,
                                ];
                            })->toArray(),
                        ];
                    })
                    ->toArray();

                $this->totalQuestions = count($this->questions);
            } else {
                // If question IDs are empty, reconstruct from UserAnswers
                // This preserves the original order for resumed quizzes
                $answeredQuestions = UserAnswer::where('quiz_attempt_id', $attempt->id)
                    ->pluck('question_id')
                    ->unique()
                    ->toArray();

                if (!empty($answeredQuestions)) {
                    // Load in the order they were answered
                    $this->questionIds = $answeredQuestions;
                    $this->questions = Question::whereIn('id', $answeredQuestions)
                        ->with('options:id,question_id,option_text,option_image,is_correct')
                        ->select('id', 'question_text', 'question_image', 'difficulty', 'explanation')
                        ->get()
                        ->sortBy(function ($q) {
                            return array_search($q->id, $this->questionIds);
                        })
                        ->map(function ($question) {
                            return [
                                'id' => $question->id,
                                'question_text' => $question->question_text,
                                'question_image' => $question->question_image,
                                'explanation' => $question->explanation,
                                'options' => $question->options->map(function ($option) {
                                    return [
                                        'id' => $option->id,
                                        'option_text' => $option->option_text,
                                        'option_image' => $option->option_image,
                                        'is_correct' => $option->is_correct,
                                    ];
                                })->toArray(),
                            ];
                        })
                        ->toArray();
                    $this->totalQuestions = count($this->questions);
                } else {
                    // No answers and no question_order - truly empty quiz
                    $this->questions = [];
                    $this->totalQuestions = 0;
                }
            }

            // Load saved answers
            $answers = UserAnswer::where('quiz_attempt_id', $attempt->id)->get();
            foreach ($answers as $answer) {
                $index = array_search($answer->question_id, $this->questionIds, true);
                if ($index !== false) {
                    $this->userAnswers[$index] = $answer->option_id;
                }
            }

            // Cache the unified state
            if (!empty($this->questions)) {
                cache()->put($cacheKey, [
                    'questions' => $this->questions,
                    'answers' => $this->userAnswers,
                    'position' => $this->currentQuestionIndex,
                ], now()->addHours(3));
            }
        }

        // Auto-submit if timer expired
        if ($this->timeRemaining <= 0 && !$this->showResults) {
            $this->handleTimerExpired();
        }
    }

    private function startNewAttempt(): void
    {
        $this->timeStarted = now();
        $this->loadQuestions();

        $this->quizAttempt = QuizAttempt::create([
            'user_id' => auth()->id(),
            'exam_type_id' => $this->exam_type,
            'subject_id' => $this->subject,
            'exam_year' => $this->year,
            'total_questions' => $this->totalQuestions,
            'correct_answers' => 0,
            'score_percentage' => 0,
            'status' => 'in_progress',
            'started_at' => $this->timeStarted,
            'time_taken_seconds' => 0,
            'question_order' => $this->questionIds,
            'current_question_index' => 0,
        ]);

        $this->attempt = $this->quizAttempt->id;
        $this->timeRemaining = $this->computeRemainingTime();
    }

    private function loadQuestions(): void
    {
        $query = Question::query()
            ->where('subject_id', $this->subject)
            ->where('is_active', true)
            ->where('status', 'approved')
            ->where('is_mock', false);

        // If exam_type is selected, filter by it
        if ($this->exam_type) {
            $query->where('exam_type_id', $this->exam_type);
        }

        // If year is selected, filter by it (with fallback for null exam_year)
        // Only apply year filter if year is set (not null/empty)
        if ($this->year) {
            $query->where(function ($q) {
                $q->where('exam_year', $this->year)
                  ->orWhere(function ($sub) {
                      $sub->whereNull('exam_year')->where('year', $this->year);
                  });
            });
        }

        // Apply limit at DB level for faster queries (if limit set, fetch exactly that many)
        if ($this->limit && $this->limit > 0) {
            $query->limit($this->limit);
        }

        $questions = $query
            ->select('id', 'question_text', 'question_image', 'difficulty', 'explanation')
            ->with('options:id,question_id,option_text,option_image,is_correct')
            ->get();

        // Shuffle in memory only if needed (after limiting)
        if ($this->shuffle == 1) {
            $questions = $questions->shuffle();
        }

        // Optimize payload: only include necessary fields
        $this->questions = $questions->map(function ($question) {
            return [
                'id' => $question->id,
                'question_text' => $question->question_text,
                'question_image' => $question->question_image,
                'explanation' => $question->explanation,
                'options' => $question->options->map(function ($option) {
                    return [
                        'id' => $option->id,
                        'option_text' => $option->option_text,
                        'option_image' => $option->option_image,
                        'is_correct' => $option->is_correct,
                    ];
                })->toArray(),
            ];
        })->toArray();

        $this->questionIds = array_column($this->questions, 'id');
        $this->totalQuestions = count($this->questions);
        $this->userAnswers = array_fill(0, $this->totalQuestions, null);
        $this->selectedAnswers = array_fill(0, $this->totalQuestions, null);
    }

    private function computeRemainingTime(): int
    {
        if (!$this->time || !$this->timeStarted) {
            return $this->time ? $this->time * 60 : 0;
        }

        $durationSeconds = $this->time * 60;
        $elapsedSeconds = $this->timeStarted->diffInSeconds(now());
        return max(0, $durationSeconds - $elapsedSeconds);
    }

    #[On('timer-expired')]
    public function handleTimerExpired()
    {
        // Only submit if a time limit is set and time has truly expired
        if ($this->time && $this->time > 0 && $this->timeRemaining <= 0) {
            $this->timeRemaining = 0;
            $this->submitQuiz();
        }
    }

    public function selectAnswer($optionId)
    {
        // Security: Verify user owns this attempt and is authenticated
        if (!auth()->check() || !$this->quizAttempt || $this->quizAttempt->user_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }

        // Throttle: prevent rapid-fire requests (max once every 300ms)
        $now = microtime(true);
        if ($now - $this->lastAnswerTime < 0.3) {
            return;
        }
        $this->lastAnswerTime = $now;

        // Validate question index
        if ($this->currentQuestionIndex < 0 || $this->currentQuestionIndex >= count($this->questions)) {
            abort(400, 'Invalid question index');
        }

        // Validate option exists and belongs to the current question
        $currentQuestion = $this->questions[$this->currentQuestionIndex] ?? null;
        if (!$currentQuestion) {
            abort(400, 'Question not found');
        }

        $validOption = collect($currentQuestion['options'])->firstWhere('id', $optionId);
        if (!$validOption) {
            abort(400, 'Invalid option selected');
        }

        $this->userAnswers[$this->currentQuestionIndex] = $optionId;
        $this->selectedAnswers[$this->currentQuestionIndex] = $optionId;

        $questionId = $currentQuestion['id'];
        // Queue answer for batch persist instead of immediate DB write
        $this->pendingAnswers[$questionId] = $optionId;

        // Update unified cache (instant local state)
        $cacheKey = "practice_attempt_{$this->quizAttempt->id}";
        cache()->put($cacheKey, [
            'questions' => $this->questions,
            'answers' => $this->userAnswers,
            'position' => $this->currentQuestionIndex,
        ], now()->addHours(3));

        // Batch persist every 3 answers or on timer
        if (count($this->pendingAnswers) >= 3) {
            $this->flushPendingAnswers();
        }
    }

    /**
     * Flush all queued answers to database in batch
     */
    public function flushPendingAnswers(): void
    {
        if (empty($this->pendingAnswers) || !$this->quizAttempt) {
            return;
        }

        foreach ($this->pendingAnswers as $questionId => $optionId) {
            $question = Question::find($questionId);
            $isCorrect = (bool) ($question?->options->firstWhere('id', $optionId)?->is_correct);

            UserAnswer::updateOrCreate(
                [
                    'quiz_attempt_id' => $this->quizAttempt->id,
                    'question_id' => $questionId,
                ],
                [
                    'user_id' => auth()->id(),
                    'option_id' => $optionId,
                    'is_correct' => $isCorrect,
                ]
            );
        }

        $this->pendingAnswers = [];
    }

    private function persistAnswer(int $questionId, int $optionId): void
    {
        if (!$this->quizAttempt) {
            return;
        }

        $question = Question::find($questionId);
        $isCorrect = (bool) ($question?->options->firstWhere('id', $optionId)?->is_correct);

        UserAnswer::updateOrCreate(
            [
                'quiz_attempt_id' => $this->quizAttempt->id,
                'question_id' => $questionId,
            ],
            [
                'user_id' => auth()->id(),
                'option_id' => $optionId,
                'is_correct' => $isCorrect,
            ]
        );
    }



    public function nextQuestion()
    {
        // Security check
        if (!auth()->check() || !$this->quizAttempt || $this->quizAttempt->user_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }

        // Persist current answer before moving
        $this->persistCurrentAnswer();
        if ($this->currentQuestionIndex < $this->totalQuestions - 1) {
            $this->currentQuestionIndex++;
            $this->debouncePositionCache();
        }
    }


    public function previousQuestion()
    {
        // Security check
        if (!auth()->check() || !$this->quizAttempt || $this->quizAttempt->user_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }

        // Persist current answer before moving
        $this->persistCurrentAnswer();
        if ($this->currentQuestionIndex > 0) {
            $this->currentQuestionIndex--;
            $this->debouncePositionCache();
        }
    }


    public function jumpToQuestion($index)
    {
        // Security check
        if (!auth()->check() || !$this->quizAttempt || $this->quizAttempt->user_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }

        // Validate index is an integer and within bounds
        if (!is_int($index) && !is_numeric($index)) {
            abort(400, 'Invalid question index');
        }

        $index = (int) $index;

        // Persist current answer before moving
        $this->persistCurrentAnswer();
        if ($index >= 0 && $index < $this->totalQuestions) {
            $this->currentQuestionIndex = $index;
            $this->debouncePositionCache();
        }
    }

    private function persistCurrentAnswer(): void
    {
        if (auth()->check() && $this->quizAttempt) {
            $questionId = $this->questions[$this->currentQuestionIndex]['id'] ?? null;
            $optionId = $this->userAnswers[$this->currentQuestionIndex] ?? null;
            if ($questionId && $optionId) {
                $this->persistAnswer($questionId, $optionId);
            }
        }
    }

    private function debouncePositionCache(): void
    {
        if ($this->positionCacheDebounce) {
            return;
        }

        $this->positionCacheDebounce = true;

        if (auth()->check() && $this->quizAttempt) {
            $cacheKey = "practice_attempt_{$this->quizAttempt->id}";
            cache()->put($cacheKey, [
                'questions' => $this->questions,
                'answers' => $this->userAnswers,
                'position' => $this->currentQuestionIndex,
            ], now()->addHours(3));

            $this->quizAttempt->update([
                'current_question_index' => $this->currentQuestionIndex,
            ]);
        }

        $this->dispatch('resetPositionDebounce');
    }

    #[On('reset-position-debounce')]
    public function resetPositionDebounce(): void
    {
        $this->positionCacheDebounce = false;
    }

    public function submitQuiz()
    {
        if (auth()->check() && $this->quizAttempt) {
            // Security: Verify user owns this attempt
            if ($this->quizAttempt->user_id !== auth()->id()) {
                abort(403, 'Unauthorized');
            }

            // Flush any remaining pending answers before finalizing
            $this->flushPendingAnswers();
            $this->finalizeAttempt();
            // Clear unified cache
            cache()->forget("practice_attempt_{$this->quizAttempt->id}");
        }

        $this->calculateScore();
        $this->showResults = true;
    }

    private function finalizeAttempt(): void
    {
        if (!$this->quizAttempt) {
            return;
        }

        $timeSpent = $this->timeStarted->diffInSeconds(now());
        $timeSpent = max(0, min($timeSpent, ($this->time ?? 1000) * 60));

        $correctCount = 0;
        foreach ($this->userAnswers as $index => $selectedOptionId) {
            if ($selectedOptionId && isset($this->questions[$index])) {
                $question = $this->questions[$index];
                $selectedOption = collect($question['options'])->firstWhere('id', $selectedOptionId);
                if ($selectedOption && $selectedOption['is_correct']) {
                    $correctCount++;
                }
            }
        }

        $percentage = $this->totalQuestions > 0 ? ($correctCount / $this->totalQuestions) * 100 : 0;

        $this->quizAttempt->update([
            'correct_answers' => $correctCount,
            'score' => $correctCount,
            'score_percentage' => $percentage,
            'time_taken_seconds' => $timeSpent,
            'total_questions' => $this->totalQuestions,
            'completed_at' => now(),
            'status' => 'completed',
        ]);
    }

    public function exitReview()
    {
        return redirect()->route('practice.home');
    }

    private function calculateScore()
    {
        $this->score = 0;

        if ($this->quizAttempt) {
            // Load from DB if persisted
            $this->score = $this->quizAttempt->correct_answers ?? 0;
        } else {
            // Calculate in-memory for guests
            foreach ($this->userAnswers as $index => $selectedOptionId) {
                if ($selectedOptionId && isset($this->questions[$index])) {
                    $question = $this->questions[$index];
                    $selectedOption = collect($question['options'])->firstWhere('id', $selectedOptionId);
                    if ($selectedOption && $selectedOption['is_correct']) {
                        $this->score++;
                    }
                }
            }
        }
    }

    #[On('update-timer')]
    public function updateTimer(): void
    {
        $this->timeRemaining = $this->computeRemainingTime();

        if ($this->timeRemaining <= 0 && !$this->showResults) {
            $this->handleTimerExpired();
        }
    }


    public function render()
    {
        return view('livewire.practice.practice-quiz');
    }
}

