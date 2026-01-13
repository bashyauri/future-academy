<?php

namespace App\Livewire\Lessons;

use App\Models\Lesson;
use App\Models\Quiz;
use App\Models\UserProgress;
use App\Models\QuizAttempt;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;

class LessonView extends Component
{
    public Lesson $lesson;
    public $progress;
    public $startTime;
    public ?Quiz $lessonQuiz = null;
    public bool $lessonQuizCompleted = false;

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

        $this->lessonQuiz = Quiz::query()
            ->active()
            ->available()
            ->where('lesson_id', $this->lesson->id)
            ->orderByDesc('created_at')
            ->first();

        if ($this->lessonQuiz) {
            $this->lessonQuizCompleted = QuizAttempt::query()
                ->where('quiz_id', $this->lessonQuiz->id)
                ->where('user_id', auth()->id())
                ->where('status', 'completed')
                ->exists();
        }
    }

    public function markComplete()
    {
        if ($this->lessonQuiz) {
            $this->lessonQuizCompleted = QuizAttempt::query()
                ->where('quiz_id', $this->lessonQuiz->id)
                ->where('user_id', auth()->id())
                ->where('status', 'completed')
                ->exists();

            if (!$this->lessonQuizCompleted) {
                session()->flash('error', 'Please complete the lesson quiz before marking this lesson as complete.');
                return redirect()->route('quiz.take', $this->lessonQuiz);
            }
        }

        $this->progress->markCompleted();

        session()->flash('success', 'Lesson marked as complete!');

        $quiz = $this->lessonQuiz;

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

    public function refreshVideoStatus()
    {
        // Reload the lesson from database to get latest video_status
        $this->lesson = $this->lesson->fresh();

        // If video is ready, dispatch event to play
        if ($this->lesson->video_type === 'local' && $this->lesson->video_status === 'ready') {
            $this->dispatch('video-ready');
        }
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

        $lessonQuiz = $this->lessonQuiz;

        if ($lessonQuiz) {
            $service = app(\App\Services\QuizGeneratorService::class);
            $stats = $service->getUserStats($lessonQuiz, auth()->user());
            $lessonQuiz->user_stats = $stats;
            $lessonQuiz->can_attempt = $lessonQuiz->canUserAttempt(auth()->user());
        }

        return view('livewire.lessons.lesson-view', [
            'nextLesson' => $nextLesson,
            'previousLesson' => $previousLesson,
            'lessonQuiz' => $lessonQuiz,
            'lessonQuizCompleted' => $this->lessonQuizCompleted,
        ]);
    }
}
