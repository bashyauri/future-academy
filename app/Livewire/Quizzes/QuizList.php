<?php

namespace App\Livewire\Quizzes;

use App\Models\Quiz;
use Livewire\Component;
use Livewire\Attributes\Layout;

class QuizList extends Component
{
    public string $search = '';
    public string $typeFilter = 'all';

    #[Layout('components.layouts.app')]
    public function render()
    {
        $quizzes = Quiz::query()
            ->where('is_mock', false)
            ->active()
            ->available()
            ->when($this->search, function ($query) {
                $query->where('title', 'like', "%{$this->search}%")
                    ->orWhere('description', 'like', "%{$this->search}%");
            })
            ->when($this->typeFilter !== 'all', function ($query) {
                $query->where('type', $this->typeFilter);
            })
            ->with('creator')
            ->latest()
            ->get()
            ->map(function ($quiz) {
                $stats = app(\App\Services\QuizGeneratorService::class)
                    ->getUserStats($quiz, auth()->user());
                $quiz->user_stats = $stats;
                $quiz->can_attempt = $quiz->canUserAttempt(auth()->user());
                return $quiz;
            });

        return view('livewire.quizzes.quiz-list', [
            'quizzes' => $quizzes,
        ]);
    }

    public function startQuiz($quizId)
    {
        return redirect()->route('quiz.take', $quizId);
    }
}
