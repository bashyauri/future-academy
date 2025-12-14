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
    public Quiz $quiz;
    public ?QuizAttempt $attempt = null;
    public $currentQuestionIndex = 0;
    public $answers = [];
    public $questions = [];
    public $shuffledOptions = [];
    public $timeRemaining = null;
    public $showResults = false;
    public $showFeedback = []; // Track which questions show feedback

    public function mount($id)
    {
        $this->quiz = Quiz::with(['questions.options', 'questions.subject', 'questions.topic'])
            ->findOrFail($id);

        if (!$this->quiz->isAvailable()) {
            abort(403, 'This quiz is not currently available.');
        }

        if (!$this->quiz->canUserAttempt(auth()->user())) {
            abort(403, 'You have reached the maximum number of attempts for this quiz.');
        }
    }

    public function startQuiz()
    {
        $service = app(QuizGeneratorService::class);

        $this->attempt = $service->generateAttempt($this->quiz, auth()->user());

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

        if ($this->quiz->isTimed()) {
            $this->timeRemaining = $this->attempt->getRemainingSeconds();
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
            $this->attempt->update(['status' => 'cancelled']);
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
