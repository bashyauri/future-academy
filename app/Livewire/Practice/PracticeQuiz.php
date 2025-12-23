<?php

namespace App\Livewire\Practice;

use App\Models\ExamType;
use App\Models\Question;
use App\Models\QuizAttempt;
use App\Models\UserAnswer;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.app')]
class PracticeQuiz extends Component
{
    #[Url]
    public $exam_type;

    #[Url]
    public $subject;

    #[Url]
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
    public ?QuizAttempt $quizAttempt = null;
    public array $questionIds = [];

    public function mount()
    {
        $this->showResults = $this->results;

        // For authenticated users, try to restore or create a persistent attempt
        if (auth()->check()) {
            $attemptFromQuery = $this->attempt ? QuizAttempt::find($this->attempt) : null;

            // If showing results, use the provided attempt (completed or in_progress)
            if ($this->showResults && $attemptFromQuery) {
                $this->quizAttempt = $attemptFromQuery;
                $this->hydrateFromAttempt($attemptFromQuery);
                return;
            }

            // Otherwise, find existing in_progress attempt
            if ($attemptFromQuery && $attemptFromQuery->status === 'in_progress') {
                $this->quizAttempt = $attemptFromQuery;
                $this->hydrateFromAttempt($attemptFromQuery);
                return;
            }

            $activeAttempt = $this->findActiveAttempt();
            if ($activeAttempt) {
                $this->quizAttempt = $activeAttempt;
                $this->hydrateFromAttempt($activeAttempt);
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

        // Load questions in the order stored
        if (!empty($this->questionIds)) {
            $this->questions = Question::whereIn('id', $this->questionIds)
                ->with('options')
                ->get()
                ->sortBy(function ($q) {
                    return array_search($q->id, $this->questionIds);
                })
                ->toArray();
        } else {
            $this->questions = [];
        }

        $this->totalQuestions = count($this->questions);
        $this->userAnswers = array_fill(0, $this->totalQuestions, null);
        $this->selectedAnswers = array_fill(0, $this->totalQuestions, null);

        // Load saved answers
        $answers = UserAnswer::where('quiz_attempt_id', $attempt->id)->get();
        foreach ($answers as $answer) {
            $index = array_search($answer->question_id, $this->questionIds, true);
            if ($index !== false) {
                $this->userAnswers[$index] = $answer->option_id;
                $this->selectedAnswers[$index] = $answer->option_id;
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
            'exam_year' => $this->year,
            'total_questions' => $this->totalQuestions,
            'correct_answers' => 0,
            'score_percentage' => 0,
            'status' => 'in_progress',
            'started_at' => $this->timeStarted,
            'time_taken_seconds' => 0,
            'question_order' => $this->questionIds,
        ]);

        $this->attempt = $this->quizAttempt->id;
        $this->timeRemaining = $this->computeRemainingTime();
    }

    private function loadQuestions(): void
    {
        $query = Question::where('exam_type_id', $this->exam_type)
            ->where('subject_id', $this->subject)
            ->where('exam_year', $this->year)
            ->with('options');

        if ($this->shuffle == 1) {
            $query->inRandomOrder();
        }

        if ($this->limit && $this->limit > 0) {
            $query->limit($this->limit);
        }

        $this->questions = $query->get()->toArray();
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
        $this->timeRemaining = 0;
        $this->submitQuiz();
    }

    public function selectAnswer($optionId)
    {
        $this->userAnswers[$this->currentQuestionIndex] = $optionId;
        $this->selectedAnswers[$this->currentQuestionIndex] = $optionId;

        // Auto-save for authenticated users
        if (auth()->check() && $this->quizAttempt) {
            $questionId = $this->questions[$this->currentQuestionIndex]['id'] ?? null;
            if ($questionId) {
                $this->persistAnswer($questionId, $optionId);
            }
        }
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
        if ($this->currentQuestionIndex < $this->totalQuestions - 1) {
            $this->currentQuestionIndex++;
        }
    }

    public function previousQuestion()
    {
        if ($this->currentQuestionIndex > 0) {
            $this->currentQuestionIndex--;
        }
    }

    public function jumpToQuestion($index)
    {
        if ($index >= 0 && $index < $this->totalQuestions) {
            $this->currentQuestionIndex = $index;
        }
    }

    public function submitQuiz()
    {
        if (auth()->check() && $this->quizAttempt) {
            $this->finalizeAttempt();
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

