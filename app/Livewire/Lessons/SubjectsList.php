<?php

namespace App\Livewire\Lessons;

use App\Models\Subject;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

class SubjectsList extends Component
{
    #[Layout('components.layouts.app')]
    public function render()
    {
        $authenticatedUser = Auth::user();
        $studentId = request()->integer('student');
        $isParentViewing = false;
        $user = $authenticatedUser;

        // If student parameter provided, verify parent is linked to this student
        if ($studentId > 0) {
            $student = User::find($studentId);
            $canViewStudent = $authenticatedUser->hasAnyRole(['admin', 'super-admin'])
                || $authenticatedUser->children()->where('users.id', $studentId)->exists();

            if (! $student || ! $canViewStudent) {
                abort(403, 'Unauthorized to view this student\'s subjects');
            }

            $user = $student;
            $isParentViewing = $user->id !== $authenticatedUser->id;
        }

        // Keep subject counts aligned with parent dashboard (enrollments source of truth).
        $selectedSubjectIds = $user->enrolledSubjects()->pluck('subjects.id')->all();

        if (empty($selectedSubjectIds)) {
            $selectedSubjectIds = $user?->selected_subjects ?? [];
        }

        $isStudent = $user && (($user->account_type ?? '') === 'student');

        if (! $isParentViewing && $isStudent && (! $user->has_completed_onboarding || empty($selectedSubjectIds))) {
            return to_route('onboarding');
        }

        $isTrial = $authenticatedUser->onTrial() && ! $authenticatedUser->hasActiveSubscription();

        $subjects = Subject::query()
            ->where('is_active', true)
            ->when(! empty($selectedSubjectIds), fn ($q) => $q->whereIn('id', $selectedSubjectIds))
            ->withCount([
                'lessons' => function ($query) use ($isTrial) {
                    $query->where('status', 'published');
                    if ($isTrial) {
                        $query->where('is_free', true);
                    }
                },
            ])->get();

        return view('livewire.lessons.subjects-list', [
            'subjects' => $subjects,
            'viewingStudent' => $user,
            'isParentViewing' => $isParentViewing,
        ]);
    }
}
