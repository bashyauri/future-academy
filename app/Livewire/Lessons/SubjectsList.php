<?php

namespace App\Livewire\Lessons;

use App\Models\Subject;
use App\Models\Lesson;
use Livewire\Component;
use Livewire\Attributes\Layout;

class SubjectsList extends Component
{
    #[Layout('components.layouts.app')]
    public function render()
    {
        $subjects = Subject::where('is_active', true)
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
