<?php

namespace App\Livewire\Quizzes;

use App\Models\Quiz;
use App\Services\QuizGeneratorService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

class QuizList extends Component
{
    public string $search = '';

    public string $typeFilter = 'all';

    #[Layout('components.layouts.app')]
    public function render()
    {
        $user = Auth::user();
        $isTrial = $user->onTrial() && ! $user->hasActiveSubscription();

        $quizzes = Quiz::query()
            ->where('is_mock', false)
            ->active()
            ->available()
            ->when($isTrial, fn ($q) => $q->where(function ($q) {
                $q->where('is_free', true)
                    ->orWhereHas('lesson', fn ($l) => $l->where('is_free', true));
            }))
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
                $stats = app(QuizGeneratorService::class)
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
