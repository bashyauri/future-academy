<?php

namespace App\Livewire\Lessons;

use Livewire\Component;

class PracticeQuestions extends Component
{
    public $questions;
    public $lessonId;
    public $currentQuestionIndex = 0;
    public $selectedAnswers = [];
    public $showResults = [];
    public $showExplanations = [];
    public $score = 0;
    public $totalAnswered = 0;
    public $isComplete = false;

    public function mount($questions, $lessonId)
    {
        $this->questions = $questions;
        $this->lessonId = $lessonId;
        $this->initializeArrays();
    }

    private function initializeArrays()
    {
        foreach ($this->questions as $index => $question) {
            $this->selectedAnswers[$index] = null;
            $this->showResults[$index] = false;
            $this->showExplanations[$index] = false;
        }
    }

    public function selectAnswer($questionIndex, $optionId)
    {
        if ($this->showResults[$questionIndex]) {
            return; // Already answered
        }

        $this->selectedAnswers[$questionIndex] = $optionId;
    }

    public function submitAnswer($questionIndex)
    {
        if (!isset($this->selectedAnswers[$questionIndex]) || $this->selectedAnswers[$questionIndex] === null) {
            $this->dispatch('notify', ['message' => 'Please select an answer', 'type' => 'warning']);
            return;
        }

        $question = $this->questions[$questionIndex];
        $selectedOption = $question->options->find($this->selectedAnswers[$questionIndex]);

        if ($selectedOption && $selectedOption->is_correct) {
            $this->score++;
        }

        $this->showResults[$questionIndex] = true;
        $this->showExplanations[$questionIndex] = true;
        $this->totalAnswered++;

        if ($this->totalAnswered === count($this->questions)) {
            $this->isComplete = true;
        }
    }

    public function resetQuestion($questionIndex)
    {
        if ($this->showResults[$questionIndex]) {
            $question = $this->questions[$questionIndex];
            $selectedOption = $question->options->find($this->selectedAnswers[$questionIndex]);

            if ($selectedOption && $selectedOption->is_correct) {
                $this->score--;
            }
            $this->totalAnswered--;
        }

        $this->selectedAnswers[$questionIndex] = null;
        $this->showResults[$questionIndex] = false;
        $this->showExplanations[$questionIndex] = false;
        $this->isComplete = false;
    }

    public function resetAll()
    {
        $this->score = 0;
        $this->totalAnswered = 0;
        $this->isComplete = false;
        $this->initializeArrays();
    }

    public function render()
    {
        return view('livewire.lessons.practice-questions');
    }
}
