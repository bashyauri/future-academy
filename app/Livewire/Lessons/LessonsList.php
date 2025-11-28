<?php

namespace App\Livewire\Lessons;

use App\Models\Subject;
use App\Models\Topic;
use App\Models\Lesson;
use Livewire\Component;
use Livewire\Attributes\Layout;

class LessonsList extends Component
{
    public $subjectId;
    public $topicId = null;

    public function mount($subject)
    {
        $this->subjectId = $subject;
    }

    #[Layout('components.layouts.app')]
    public function render()
    {
        $subject = Subject::findOrFail($this->subjectId);

        $topics = Topic::where('subject_id', $this->subjectId)
            ->withCount([
                'lessons' => function ($query) {
                    $query->where('status', 'published');
                }
            ])
            ->get();

        $lessonsQuery = Lesson::with(['subject', 'topic'])
            ->where('subject_id', $this->subjectId)
            ->where('status', 'published')
            ->ordered();

        if ($this->topicId) {
            $lessonsQuery->where('topic_id', $this->topicId);
        }

        $lessons = $lessonsQuery->get();

        return view('livewire.lessons.lessons-list', [
            'subject' => $subject,
            'topics' => $topics,
            'lessons' => $lessons,
        ]);
    }
}
