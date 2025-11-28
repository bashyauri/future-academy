<?php

namespace App\Livewire\Lessons;

use App\Models\Lesson;
use App\Models\UserProgress;
use Livewire\Component;
use Livewire\Attributes\Layout;

class LessonView extends Component
{
    public Lesson $lesson;
    public $progress;
    public $startTime;

    public function mount($id)
    {
        $this->lesson = Lesson::with(['subject', 'topic', 'questions.options'])->findOrFail($id);

        if (!$this->lesson->canUserAccess(auth()->user())) {
            abort(403, 'You do not have access to this lesson.');
        }

        // Get or create progress
        $this->progress = UserProgress::firstOrCreate([
            'user_id' => auth()->id(),
            'lesson_id' => $this->lesson->id,
        ], [
            'type' => 'lesson',
            'started_at' => now(),
        ]);

        $this->startTime = now();
    }

    public function markComplete()
    {
        $this->progress->markCompleted();

        session()->flash('success', 'Lesson marked as complete!');

        // Find next lesson
        $nextLesson = Lesson::where('subject_id', $this->lesson->subject_id)
            ->where('status', 'published')
            ->where('order', '>', $this->lesson->order)
            ->ordered()
            ->first();

        if ($nextLesson) {
            return redirect()->route('lessons.view', $nextLesson);
        }

        return redirect()->route('lessons.list', $this->lesson->subject_id);
    }

    public function updateProgress($percentage)
    {
        $this->progress->updateProgress($percentage);
    }

    public function destroy()
    {
        // Track time spent on page before leaving
        if ($this->startTime) {
            $timeSpent = now()->diffInSeconds($this->startTime);
            $this->progress->addTimeSpent($timeSpent);
        }
    }

    #[Layout('components.layouts.app')]
    public function render()
    {
        $nextLesson = Lesson::where('subject_id', $this->lesson->subject_id)
            ->where('status', 'published')
            ->where('order', '>', $this->lesson->order)
            ->ordered()
            ->first();

        $previousLesson = Lesson::where('subject_id', $this->lesson->subject_id)
            ->where('status', 'published')
            ->where('order', '<', $this->lesson->order)
            ->orderBy('order', 'desc')
            ->first();

        return view('livewire.lessons.lesson-view', [
            'nextLesson' => $nextLesson,
            'previousLesson' => $previousLesson,
        ]);
    }
}
