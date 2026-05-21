<?php

namespace App\Livewire\Lessons;

use App\Models\Lesson;
use App\Models\Subject;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

class LessonsList extends Component
{
    public $subjectId;

    public $topicId = null;

    public ?User $viewingStudent = null;

    public bool $isParentViewing = false;

    public function mount($subject)
    {
        $this->subjectId = $subject;

        $authenticatedUser = Auth::user();
        $studentId = request()->integer('student');
        $this->viewingStudent = $authenticatedUser;

        if ($studentId > 0) {
            $student = User::find($studentId);
            $canViewStudent = $authenticatedUser->hasAnyRole(['admin', 'super-admin'])
                || $authenticatedUser->children()->where('users.id', $studentId)->exists();

            if (! $student || ! $canViewStudent) {
                abort(403, 'Unauthorized to view this student\'s lessons.');
            }

            $this->viewingStudent = $student;
            $this->isParentViewing = $this->viewingStudent->id !== $authenticatedUser->id;
        }

        if ($this->isParentViewing) {
            $isEnrolled = $this->viewingStudent
                ->enrolledSubjects()
                ->where('subjects.id', $this->subjectId)
                ->exists();

            if (! $isEnrolled) {
                abort(403, 'Student is not enrolled in this subject.');
            }
        }
    }

    #[Layout('components.layouts.app')]
    public function render()
    {
        $subject = Subject::findOrFail($this->subjectId);

        $viewingUser = $this->viewingStudent ?? Auth::user();
        $isTrial = $viewingUser->onTrial() && ! $viewingUser->hasActiveSubscription();

        $topics = Topic::where('subject_id', $this->subjectId)
            ->withCount([
                'lessons as published_lessons_count' => function ($query) {
                    $query->where('status', 'published');
                },
                'lessons as free_lessons_count' => function ($query) {
                    $query->where('status', 'published')
                        ->where('is_free', true);
                },
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
            'isTrial' => $isTrial,
            'viewingStudent' => $this->viewingStudent,
            'isParentViewing' => $this->isParentViewing,
        ]);
    }
}
