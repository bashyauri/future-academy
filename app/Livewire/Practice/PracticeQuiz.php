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
    public $timeStarted;
    public $timeRemaining;
    public $showResults = false;
    public $score = 0;
    public $totalQuestions = 0;
    #[Locked]
    public ?QuizAttempt $quizAttempt = null;

    /**
     * Exit the quiz and allow user to continue later (without submitting).
     */
    public function exitQuiz()
    {
        // Save current position and answers to cache before exiting
        if (auth()->check() && $this->quizAttempt) {
            if ($this->quizAttempt->user_id !== auth()->id()) {
                abort(403, 'Unauthorized');
            }

            // Save answers only when exiting
            $this->saveAnswers();
        }

        return redirect()->route('practice.home');
    }

    #[Computed]
    public function currentQuestion()
    {
        return $this->questions[$this->currentQuestionIndex] ?? null;
    }

    public function mount()
    {
        $this->showResults = false;

        // Validate required parameters
        if (!$this->subject) {
            session()->flash('error', 'Invalid subject. Please select a subject to practice.');
            return redirect()->route('practice.home');
        }

        // Validate subject exists
        $subject = Subject::where('id', $this->subject)->where('is_active', true)->first();
        if (!$subject) {
            session()->flash('error', 'The selected subject is not available.');
            return redirect()->route('practice.home');
        }

        // Validate exam_type if provided
        if ($this->exam_type) {
            $examType = ExamType::where('id', $this->exam_type)->where('is_active', true)->first();
            if (!$examType) {
                session()->flash('error', 'The selected exam type is not available.');
                return redirect()->route('practice.home');
            }
        }

        // For authenticated users
        if (auth()->check()) {
            $attemptFromQuery = $this->attempt ? QuizAttempt::find($this->attempt) : null;

            // Verify ownership
            if ($attemptFromQuery && $attemptFromQuery->user_id !== auth()->id()) {
                abort(403, 'Unauthorized attempt access');
            }

            // If showing results, display results
            if ($this->results && $attemptFromQuery) {
                $this->quizAttempt = $attemptFromQuery;
                $this->loadQuestionsAndAnswers($attemptFromQuery);
                $this->showResults = true;
                return;
            }

            // If attempt provided and in-progress, resume it
            if ($attemptFromQuery && $attemptFromQuery->status === 'in_progress') {
                $this->quizAttempt = $attemptFromQuery;
                $this->loadQuestionsAndAnswers($attemptFromQuery);
                return;
            }

            // Find active in-progress attempt
            $activeAttempt = QuizAttempt::where('user_id', auth()->id())
                ->where('exam_type_id', $this->exam_type)
                ->where('exam_year', $this->year)
                ->where('status', 'in_progress')
                ->latest('created_at')
                ->first();

            if ($activeAttempt) {
                $this->quizAttempt = $activeAttempt;
                $this->loadQuestionsAndAnswers($activeAttempt);
                return;
            }

            // If attempt is completed, show results
            if ($attemptFromQuery && $attemptFromQuery->status === 'completed') {
                $this->quizAttempt = $attemptFromQuery;
                $this->loadQuestionsAndAnswers($attemptFromQuery);
                $this->showResults = true;
                return;
            }

            // Start new attempt
            if (!$this->showResults) {
                $this->startNewAttempt();
            } else {
                return redirect()->route('practice.home');
            }
        } else {
            // Guest: fresh session
            $this->loadAllQuestions();
        }
    }

    /**
     * Load ALL questions upfront and cache them together (like MockQuiz)
     */
    private function loadAllQuestions(): void
    {
        $query = Question::query()
            ->where('subject_id', $this->subject)
            ->where('is_active', true)
            ->where('status', 'approved')
            ->where('is_mock', false);

        if ($this->exam_type) {
            $query->where('exam_type_id', $this->exam_type);
        }

        if ($this->year) {
            $query->where(function ($q) {
                $q->where('exam_year', $this->year)
                  ->orWhere(function ($sub) {
                      $sub->whereNull('exam_year')->where('year', $this->year);
                  });
            });
        }

        if ($this->limit && $this->limit > 0) {
            $query->limit($this->limit);
        }

        $questions = $query
            ->select('id', 'question_text', 'question_image', 'difficulty', 'explanation')
            ->with('options:id,question_id,option_text,option_image,is_correct')
            ->get();

        // Shuffle after fetching
        if ($this->shuffle == 1) {
            $questions = $questions->shuffle();
            $questions = $questions->map(function ($question) {
                $question->options = $question->options->shuffle();
                return $question;
            });
        }

        // Convert to array format
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

        $this->totalQuestions = count($this->questions);
        $this->userAnswers = array_fill(0, $this->totalQuestions, null);
    }

    /**
     * Load questions and answers from cache or database
     */
    private function loadQuestionsAndAnswers(QuizAttempt $attempt): void
    {
        $this->attempt = $attempt->id;
        $this->timeStarted = $attempt->started_at;
        $this->timeRemaining = $this->computeRemainingTime();
        $this->currentQuestionIndex = $attempt->current_question_index ?? 0;

        // Try unified cache first
        $cacheKey = "practice_attempt_{$attempt->id}";
        $cached = cache()->get($cacheKey);

        if ($cached) {
            $this->questions = $cached['questions'];
            $this->userAnswers = $cached['answers'];
            $this->currentQuestionIndex = $cached['position'];
            $this->totalQuestions = count($this->questions);
            return;
        }

        // Load from database if cache miss
        $questionIds = $attempt->question_order ?? [];
        if (is_array($questionIds) && !empty($questionIds)) {
            $firstValue = reset($questionIds);
            if (is_array($firstValue)) {
                $questionIds = array_merge(...array_values($questionIds));
            }
        }

        if (!empty($questionIds)) {
            $questions = Question::whereIn('id', $questionIds)
                ->with('options:id,question_id,option_text,option_image,is_correct')
                ->select('id', 'question_text', 'question_image', 'difficulty', 'explanation')
                ->get()
                ->sortBy(function ($q) use ($questionIds) {
                    return array_search($q->id, $questionIds);
                });

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

            $this->totalQuestions = count($this->questions);
        }

        // Load existing answers
        $answers = UserAnswer::where('quiz_attempt_id', $attempt->id)
            ->with('question:id')
            ->get();

        foreach ($answers as $answer) {
            $index = array_search($answer->question_id, array_column($this->questions, 'id'));
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

        // Auto-submit if time expired
        if ($this->timeRemaining <= 0 && !$this->showResults) {
            $this->handleTimerExpired();
        }
    }

    private function startNewAttempt(): void
    {
        $this->timeStarted = now();
        $this->loadAllQuestions();

        $questionIds = array_column($this->questions, 'id');
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
            'question_order' => $questionIds,
            'current_question_index' => 0,
        ]);

        $this->attempt = $this->quizAttempt->id;
        $this->timeRemaining = $this->computeRemainingTime();

        // Cache the initial state
        $cacheKey = "practice_attempt_{$this->quizAttempt->id}";
        cache()->put($cacheKey, [
            'questions' => $this->questions,
            'answers' => $this->userAnswers,
            'position' => $this->currentQuestionIndex,
        ], now()->addHours(3));
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

    /**
     * Select an answer - just update local state, don't save to DB yet
     */
    public function selectAnswer($optionId)
    {
        if (!auth()->check() || !$this->quizAttempt || $this->quizAttempt->user_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }

        // Validate question and option
        $currentQuestion = $this->questions[$this->currentQuestionIndex] ?? null;
        if (!$currentQuestion) {
            abort(400, 'Question not found');
        }

        $validOption = collect($currentQuestion['options'])->firstWhere('id', $optionId);
        if (!$validOption) {
            abort(400, 'Invalid option');
        }

        // Update local state
        $this->userAnswers[$this->currentQuestionIndex] = $optionId;

        // Update unified cache
        $cacheKey = "practice_attempt_{$this->quizAttempt->id}";
        cache()->put($cacheKey, [
            'questions' => $this->questions,
            'answers' => $this->userAnswers,
            'position' => $this->currentQuestionIndex,
        ], now()->addHours(3));
    }



    /**
     * Navigation - just update position and cache state
     */
    public function nextQuestion(): void
    {
        if ($this->currentQuestionIndex < $this->totalQuestions - 1) {
            $this->currentQuestionIndex++;
            $this->updatePositionCache();
        }
    }

    public function previousQuestion(): void
    {
        if ($this->currentQuestionIndex > 0) {
            $this->currentQuestionIndex--;
            $this->updatePositionCache();
        }
    }

    public function jumpToQuestion($index): void
    {
        $index = (int) $index;
        if ($index >= 0 && $index < $this->totalQuestions) {
            $this->currentQuestionIndex = $index;
            $this->updatePositionCache();
        }
    }

    /**
     * Update position in cache
     */
    private function updatePositionCache(): void
    {
        if (auth()->check() && $this->quizAttempt) {
            $cacheKey = "practice_attempt_{$this->quizAttempt->id}";
            cache()->put($cacheKey, [
                'questions' => $this->questions,
                'answers' => $this->userAnswers,
                'position' => $this->currentQuestionIndex,
            ], now()->addHours(3));
        }
    }

    #[On('timer-expired')]
    public function handleTimerExpired()
    {
        if ($this->time && $this->time > 0 && $this->timeRemaining <= 0) {
            $this->timeRemaining = 0;
            $this->submitQuiz();
        }
    }

    /**
     * Save all answers to database (called on exit or submit)
     */
    private function saveAnswers(): void
    {
        if (!$this->quizAttempt) {
            return;
        }

        // Batch insert all answers
        foreach ($this->questions as $index => $question) {
            $userAnswer = $this->userAnswers[$index] ?? null;
            if ($userAnswer) {
                $selectedOption = collect($question['options'])->firstWhere('id', $userAnswer);
                $isCorrect = $selectedOption && $selectedOption['is_correct'] ? true : false;

                UserAnswer::updateOrCreate(
                    [
                        'quiz_attempt_id' => $this->quizAttempt->id,
                        'question_id' => $question['id'],
                    ],
                    [
                        'user_id' => auth()->id(),
                        'option_id' => $userAnswer,
                        'is_correct' => $isCorrect,
                    ]
                );
            }
        }
    }

    /**
     * Submit quiz - save answers and calculate score
     */
    public function submitQuiz()
    {
        if (auth()->check() && $this->quizAttempt) {
            if ($this->quizAttempt->user_id !== auth()->id()) {
                abort(403, 'Unauthorized');
            }

            // Save all answers to database
            $this->saveAnswers();

            // Calculate and update attempt
            $this->calculateScore();
            $timeSpent = $this->timeStarted->diffInSeconds(now());
            $timeSpent = max(0, min($timeSpent, ($this->time ?? 1000) * 60));

            $this->quizAttempt->update([
                'correct_answers' => $this->score,
                'score' => $this->score,
                'score_percentage' => $this->totalQuestions > 0 ? ($this->score / $this->totalQuestions) * 100 : 0,
                'time_taken_seconds' => $timeSpent,
                'total_questions' => $this->totalQuestions,
                'completed_at' => now(),
                'status' => 'completed',
            ]);

            // Clear cache
            cache()->forget("practice_attempt_{$this->quizAttempt->id}");
        }

        $this->showResults = true;
    }

    /**
     * Calculate score from current answers
     */
    private function calculateScore(): void
    {
        $this->score = 0;

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

