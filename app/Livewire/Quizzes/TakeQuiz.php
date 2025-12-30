<?php

namespace App\Livewire\Quizzes;

use App\Models\Question;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Services\QuizGeneratorService;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;

class TakeQuiz extends Component
{
    // Computed property for Blade: are there any attached questions?
    public function getHasAvailableQuestionsProperty()
    {
        return $this->quiz->questions()->count() > 0;
    }
    public Quiz $quiz;
    public ?QuizAttempt $attempt = null;
    public $currentQuestionIndex = 0;
    public $answers = [];
    public $questions = [];
    public $shuffledOptions = [];
    public $timeRemaining = null;
    public $showResults = false;
    public $showFeedback = []; // Track which questions show feedback
    public $autoSaveStatus = 'idle'; // idle, saving, saved
    public $lastSavedTime = null;
    public $autoSaveInterval = 15; // Auto-save every 15 seconds

    public function mount($id)
    {
        $this->quiz = Quiz::with(['questions.options', 'questions.subject', 'questions.topic'])
            ->findOrFail($id);

        if (!$this->quiz->isAvailable()) {
            abort(403, 'This quiz is not currently available.');
        }

        // Check for active quiz attempt (in_progress status)
        // Get the LATEST attempt, not the oldest
        $activeAttempt = $this->quiz->attempts()
            ->where('user_id', auth()->id())
            ->where('status', 'in_progress')
            ->latest('created_at')
            ->first();

        if ($activeAttempt) {
            // Load existing attempt instead of creating a new one
            $this->attempt = $activeAttempt;
            $this->loadAttemptQuestions();

            // Calculate remaining time from server: (started_at + duration) - now
            if ($this->quiz->isTimed()) {
                $this->timeRemaining = $this->calculateRemainingSeconds();

                // If time has expired, auto-submit
                if ($this->timeRemaining <= 0) {
                    $this->handleTimerExpired();
                }
            }
            return;
        }

        // No active attempt exists, user can start a new one
        if (!$this->quiz->canUserAttempt(auth()->user())) {
            abort(403, 'You have reached the maximum number of attempts for this quiz.');
        }
    }

    private function loadAttemptQuestions()
    {
        if (!$this->attempt) {
            return;
        }

        $service = app(QuizGeneratorService::class);

        // Load questions in the order specified by the attempt
        $questionIds = $this->attempt->getQuestionIds();

        // Fetch questions by IDs and maintain the order
        $this->questions = Question::with(['options', 'subject', 'topic', 'examType'])
            ->whereIn('id', $questionIds)
            ->get()
            ->sortBy(function ($question) use ($questionIds) {
                return array_search($question->id, $questionIds);
            })
            ->values();

        // Shuffle options for each question if enabled
        foreach ($this->questions as $question) {
            $this->shuffledOptions[$question->id] = $service->getShuffledOptions($this->quiz, $question);
        }

        // Load user's saved answers
        $answers = $this->attempt->answers()
            ->pluck('option_id', 'question_id')
            ->toArray();
        $this->answers = $answers;
    }

    private function calculateRemainingSeconds()
    {
        if (!$this->attempt || !$this->quiz->isTimed()) {
            return null;
        }

        // Ensure started_at and duration_minutes are set
        if (!$this->attempt->started_at || !$this->quiz->duration_minutes) {
            \Log::warning('Quiz timer issue', [
                'attempt_id' => $this->attempt->id,
                'started_at' => $this->attempt->started_at,
                'duration_minutes' => $this->quiz->duration_minutes,
            ]);
            return null;
        }
        // Use timestamps to avoid Carbon diff quirks and clamp to a valid range
        $durationSeconds = (int) ($this->quiz->duration_minutes * 60);
        $endTimestamp = $this->attempt->started_at->getTimestamp() + $durationSeconds;
        $nowTimestamp = now()->getTimestamp();

        $remaining = $endTimestamp - $nowTimestamp;

        if ($remaining <= 0) {
            return 0;
        }

        // Guard against clock skew pushing the timer back up
        if ($remaining > $durationSeconds) {
            $remaining = $durationSeconds;
        }

        return $remaining;
    }

    #[On('update-timer')]
    public function updateTimerFromServer()
    {
        if (!$this->attempt || !$this->quiz->isTimed()) {
            return;
        }

        // Recalculate remaining time from server
        $this->timeRemaining = $this->calculateRemainingSeconds();

        // Push the updated value to the browser timer
        $this->dispatch('update-timer-value', value: $this->timeRemaining);

        // Auto-submit if time has expired
        if ($this->timeRemaining <= 0) {
            $this->handleTimerExpired();
        }
    }

    public function startQuiz()
    {
        $service = app(QuizGeneratorService::class);

        $this->attempt = $service->generateAttempt($this->quiz, auth()->user());

        $this->loadAttemptQuestions();

        if ($this->quiz->isTimed()) {
            $this->timeRemaining = $this->calculateRemainingSeconds();
        }
    }

    #[On('timer-expired')]
    public function handleTimerExpired()
    {
        $this->submitQuiz(true);
    }

    public function answerQuestion($questionId, $optionId)
    {
        $this->answers[$questionId] = $optionId;
        $this->showFeedback[$questionId] = true; // Show feedback immediately

        $service = app(QuizGeneratorService::class);
        $service->submitAnswer($this->attempt, $questionId, $optionId);

        // Auto-save immediately after answer
        $this->autoSaveAnswers();
    }

    public function autoSaveAnswers()
    {
        if (!$this->attempt || $this->attempt->isCompleted()) {
            return;
        }

        try {
            $this->autoSaveStatus = 'saving';
            $service = app(QuizGeneratorService::class);

            // Save all current answers
            foreach ($this->answers as $questionId => $optionId) {
                $service->submitAnswer($this->attempt, $questionId, $optionId);
            }

            $this->lastSavedTime = now()->format('H:i:s');
            $this->autoSaveStatus = 'saved';

            // Reset saved status after 2 seconds
            $this->dispatch('resetAutoSaveStatus');
        } catch (\Throwable $e) {
            \Log::error('Quiz auto-save failed: ' . $e->getMessage());
            $this->autoSaveStatus = 'idle';
        }
    }

    #[On('reset-auto-save-status')]
    public function resetAutoSaveStatus()
    {
        $this->autoSaveStatus = 'idle';
    }

    public function nextQuestion()
    {
        if ($this->currentQuestionIndex < count($this->questions) - 1) {
            $this->currentQuestionIndex++;
        }
    }

    public function previousQuestion()
    {
        if ($this->currentQuestionIndex > 0) {
            $this->currentQuestionIndex--;
        }
    }

    public function goToQuestion($index)
    {
        $this->currentQuestionIndex = $index;
    }

    public function exitQuiz()
    {
        if ($this->attempt && !$this->attempt->isCompleted()) {
            $this->attempt->update(['status' => 'abandoned']);
        }

        return redirect()->route('quizzes.index');
    }

    public function getCurrentQuestion()
    {
        return $this->questions[$this->currentQuestionIndex] ?? null;
    }

    public function isAnswered($questionId)
    {
        return isset($this->answers[$questionId]);
    }

    public function showingFeedback($questionId)
    {
        return isset($this->showFeedback[$questionId]) && $this->showFeedback[$questionId];
    }

    public function submitQuiz($timedOut = false)
    {
        if (!$this->attempt || $this->attempt->isCompleted()) {
            return;
        }

        // Final auto-save before submit
        $this->autoSaveAnswers();

        $service = app(QuizGeneratorService::class);

        if ($timedOut) {
            $this->attempt->update(['status' => 'timed_out']);
        }

        $service->completeAttempt($this->attempt);

        $this->attempt->refresh();
        $this->showResults = true;
    }

    #[Layout('components.layouts.app')]
    public function render()
    {
        $currentQuestion = $this->getCurrentQuestion();

        return view('livewire.quizzes.take-quiz', [
            'currentQuestion' => $currentQuestion,
            'totalQuestions' => count($this->questions),
            'answeredCount' => count(array_filter($this->answers)),
        ]);
    }
}
