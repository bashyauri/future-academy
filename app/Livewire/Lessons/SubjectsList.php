<?php

namespace App\Livewire\Lessons;

use App\Models\Subject;
use App\Models\Lesson;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\Attributes\Layout;

class SubjectsList extends Component
{
    #[Layout('components.layouts.app')]
    public function render()
    {
        $user = Auth::user();
        $selectedSubjectIds = $user?->selected_subjects ?? [];

        $isStudent = $user && (($user->account_type ?? '') === 'student');

        if ($isStudent && (!$user->has_completed_onboarding || empty($selectedSubjectIds))) {

            to_route('onboarding');
        }

        $subjects = Subject::query()
            ->where('is_active', true)
            ->when(!empty($selectedSubjectIds), fn($q) => $q->whereIn('id', $selectedSubjectIds))
            ->withCount([
                'lessons' => function ($query) {
                    $query->where('status', 'published');
                }
            ])->get();

        return view('livewire.lessons.subjects-list', [
            'subjects' => $subjects,
        ]);
    }
}
