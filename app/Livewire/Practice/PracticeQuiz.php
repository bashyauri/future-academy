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
    public $allQuestionIds = []; // Store all question IDs for lazy loading
    public $questionsPerPage = 30; // Pre-load 30 questions at a time (up from 5)
    public $loadedUpToIndex = -1; // Track which questions have been loaded
    #[Locked]
    public ?QuizAttempt $quizAttempt = null;
    public $csrfToken = ''; // For autosave fetch requests

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
        $this->csrfToken = csrf_token();
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
     * Load all selected questions upfront (no lazy loading)
     * All questions loaded into browser memory for instant navigation
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

        // Get total count
        $totalQuestions = $query->count();

        if ($this->limit && $this->limit > 0) {
            $totalQuestions = min($this->limit, $totalQuestions);
        }

        $this->totalQuestions = $totalQuestions;
        $this->allQuestionIds = $query
            ->when($this->limit && $this->limit > 0, fn($q) => $q->limit($this->limit))
            ->pluck('id')
            ->toArray();

        // Apply shuffle to all question IDs if needed
        if ($this->shuffle == 1) {
            shuffle($this->allQuestionIds);
        }

        // Initialize answers array
        $this->userAnswers = array_fill(0, $this->totalQuestions, null);

        // Load ALL questions at once (not in batches)
        $this->questions = [];
        $this->loadedUpToIndex = -1;

        if (!empty($this->allQuestionIds)) {
            $this->loadAllQuestionsBatch();
        }
    }

    /**
     * Load ALL questions at once (no batching)
     * All questions loaded into browser memory for instant navigation
     */
    private function loadAllQuestionsBatch(): void
    {
        if (empty($this->allQuestionIds)) {
            return;
        }

        // Load all questions in one go
        $allQuestions = Question::whereIn('id', $this->allQuestionIds)
            ->select('id', 'question_text', 'question_image', 'difficulty', 'explanation')
            ->with('options:id,question_id,option_text,option_image,is_correct')
            ->get();

        // Sort by the order in allQuestionIds to maintain shuffle
        $allQuestions = $allQuestions->sortBy(function ($q) {
            return array_search($q->id, $this->allQuestionIds);
        });

        // Convert to array format
        $questionArray = $allQuestions->map(function ($question) {
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

        // Shuffle options if needed
        if ($this->shuffle == 1) {
            $questionArray = array_map(function ($question) {
                $question['options'] = array_values($question['options']);
                shuffle($question['options']);
                return $question;
            }, $questionArray);
        }

        // Set all questions
        $this->questions = $questionArray;
        $this->loadedUpToIndex = $this->totalQuestions - 1;
    }

    /**
     * Load a batch of questions by index range
     */
    private function loadQuestionsBatch(int $startIndex): void
    {
        if ($startIndex >= $this->totalQuestions) {
            return;
        }

        $endIndex = min($startIndex + $this->questionsPerPage - 1, $this->totalQuestions - 1);
        $batchIds = array_slice($this->allQuestionIds, $startIndex, $this->questionsPerPage);

        if (empty($batchIds)) {
            return;
        }

        $batchQuestions = Question::whereIn('id', $batchIds)
            ->select('id', 'question_text', 'question_image', 'difficulty', 'explanation')
            ->with('options:id,question_id,option_text,option_image,is_correct')
            ->get();

        // Sort by the order in allQuestionIds to maintain shuffle
        $batchQuestions = $batchQuestions->sortBy(function ($q) use ($batchIds) {
            return array_search($q->id, $batchIds);
        });

        // Convert to array format and merge with existing questions
        $newQuestions = $batchQuestions->map(function ($question) {
            return [
                'id' => $question->id,
                'question_text' => $question->question_text,
                'question_image' => $question->question_image,
                'explanation' => $question->explanation,
                'options' => $question->options->map(function ($option) {
                    if ($this->shuffle == 1) {
                        // Already shuffled via relationship
                    }
                    return [
                        'id' => $option->id,
                        'option_text' => $option->option_text,
                        'option_image' => $option->option_image,
                        'is_correct' => $option->is_correct,
                    ];
                })->toArray(),
            ];
        })->toArray();

        // Shuffle options if needed (per batch)
        if ($this->shuffle == 1) {
            $newQuestions = array_map(function ($question) {
                $question['options'] = array_values($question['options']);
                shuffle($question['options']);
                return $question;
            }, $newQuestions);
        }

        // Merge into main questions array
        $this->questions = array_merge($this->questions, $newQuestions);
        $this->loadedUpToIndex = $endIndex;
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

        if ($cached && isset($cached['questions'])) {
            // Full cache available (old format with questions)
            $this->questions = $cached['questions'];
            $this->userAnswers = $cached['answers'];
            $this->currentQuestionIndex = $cached['position'];
            $this->allQuestionIds = $cached['all_question_ids'] ?? array_column($this->questions, 'id');
            $this->loadedUpToIndex = $cached['loaded_up_to_index'] ?? count($this->questions) - 1;
            $this->totalQuestions = $cached['total_questions'] ?? count($this->allQuestionIds);
            return;
        } elseif ($cached && isset($cached['answers'])) {
            // Partial cache (new autosave format with answers only)
            // Load questions from database, restore answers from cache
            $this->userAnswers = $cached['answers'];
            $this->currentQuestionIndex = $cached['position'] ?? 0;
            // Continue to load questions from database below
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
            $this->totalQuestions = count($questionIds);
            $this->allQuestionIds = $questionIds;
            $this->loadedUpToIndex = -1;
            $this->questions = [];

            // Load ALL questions (not in batches)
            $this->loadAllQuestionsBatch();
        }

        // Initialize userAnswers array if not already set from cache
        if (empty($this->userAnswers)) {
            $this->userAnswers = array_fill(0, $this->totalQuestions, null);
        }

        // Load existing answers from database (only if not loaded from cache)
        $answers = UserAnswer::where('quiz_attempt_id', $attempt->id)
            ->with('question:id')
            ->get();

        foreach ($answers as $answer) {
            $index = array_search($answer->question_id, $this->allQuestionIds);
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
                'all_question_ids' => $this->allQuestionIds,
                'loaded_up_to_index' => $this->loadedUpToIndex,
                'total_questions' => $this->totalQuestions,
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

        $questionIds = $this->allQuestionIds; // Use lazy-loaded IDs
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
            'all_question_ids' => $this->allQuestionIds,
            'loaded_up_to_index' => $this->loadedUpToIndex,
            'total_questions' => $this->totalQuestions,
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
     * Placeholder method - answer selection now handled by Alpine.js on client-side
     * This method is kept for backward compatibility but not called
     */
    public function selectAnswer($optionId)
    {
        // Answer selection is now purely client-side via Alpine.js
        // This method serves as a fallback if needed
        return;
    }



    /**
     * Navigation - just update position and cache state
     */
    public function nextQuestion(): void
    {
        if ($this->currentQuestionIndex < $this->totalQuestions - 1) {
            $this->currentQuestionIndex++;

            // Trigger lazy load if approaching end of loaded batch
            if ($this->currentQuestionIndex > $this->loadedUpToIndex - 2 && $this->loadedUpToIndex < $this->totalQuestions - 1) {
                $this->loadQuestionsBatch($this->loadedUpToIndex + 1);
            }

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

            // Trigger lazy load if jumping beyond loaded batch
            while ($index > $this->loadedUpToIndex && $this->loadedUpToIndex < $this->totalQuestions - 1) {
                $this->loadQuestionsBatch($this->loadedUpToIndex + 1);
            }

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
                'all_question_ids' => $this->allQuestionIds,
                'loaded_up_to_index' => $this->loadedUpToIndex,
                'total_questions' => $this->totalQuestions,
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

        // Load ALL questions first if we're using lazy loading
        if (!empty($this->allQuestionIds) && count($this->questions) < $this->totalQuestions) {
            // Load remaining questions for answer validation
            $remainingIds = array_slice($this->allQuestionIds, $this->loadedUpToIndex + 1);
            if (!empty($remainingIds)) {
                $remainingQuestions = Question::whereIn('id', $remainingIds)
                    ->select('id', 'question_text', 'question_image', 'difficulty', 'explanation')
                    ->with('options:id,question_id,option_text,option_image,is_correct')
                    ->get();

                // Sort by the order in allQuestionIds
                $remainingQuestions = $remainingQuestions->sortBy(function ($q) use ($remainingIds) {
                    return array_search($q->id, $remainingIds);
                });

                // Convert and merge
                $newQuestions = $remainingQuestions->map(function ($question) {
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

                $this->questions = array_merge($this->questions, $newQuestions);
            }
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

