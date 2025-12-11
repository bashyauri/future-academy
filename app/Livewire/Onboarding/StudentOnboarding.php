<?php

namespace App\Livewire\Onboarding;

use App\Models\ExamType;
use App\Models\Stream;
use App\Models\Subject;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class StudentOnboarding extends Component
{
    public $step = 1;
    public $selectedStream = null;
    public $selectedExamTypes = [];
    public $selectedSubjects = [];
    public $streams;
    public $examTypes;
    public $subjects;

    public function mount()
    {
        // Redirect if already completed onboarding
        if (auth()->user()->has_completed_onboarding) {
            return redirect()->route('student.dashboard');
        }

        $this->streams = Stream::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $this->examTypes = ExamType::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        $this->subjects = Subject::where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    public function selectStream($streamSlug)
    {
        $this->selectedStream = $streamSlug;

        if ($streamSlug === 'custom') {
            // Go directly to subject selection
            $this->step = 3;
        } else {
            // Go to exam type selection
            $this->step = 2;
        }
    }

    public function toggleExamType($examTypeId)
    {
        if (in_array($examTypeId, $this->selectedExamTypes)) {
            $this->selectedExamTypes = array_values(array_diff($this->selectedExamTypes, [$examTypeId]));
        } else {
            $this->selectedExamTypes[] = $examTypeId;
        }
    }

    public function nextToSubjects()
    {
        $this->validate([
            'selectedExamTypes' => 'required|array|min:1',
        ], [
            'selectedExamTypes.required' => 'Please select at least one exam type.',
            'selectedExamTypes.min' => 'Please select at least one exam type.',
        ]);

        $this->step = 3;
    }

    public function toggleSubject($subjectId)
    {
        if (in_array($subjectId, $this->selectedSubjects)) {
            $this->selectedSubjects = array_values(array_diff($this->selectedSubjects, [$subjectId]));
        } else {
            $this->selectedSubjects[] = $subjectId;
        }
    }

    public function completeOnboarding()
    {
        $this->validate([
            'selectedSubjects' => 'required|array|min:1',
        ], [
            'selectedSubjects.required' => 'Please select at least one subject.',
            'selectedSubjects.min' => 'Please select at least one subject.',
        ]);

        $user = auth()->user();

        // Update user with selections
        $user->update([
            'stream' => $this->selectedStream,
            'exam_types' => $this->selectedExamTypes,
            'selected_subjects' => $this->selectedSubjects,
            'has_completed_onboarding' => true,
        ]);

        // Create enrollments for selected subjects
        foreach ($this->selectedSubjects as $subjectId) {
            $user->enrollments()->firstOrCreate([
                'subject_id' => $subjectId,
            ], [
                'enrolled_at' => now(),
                'is_active' => true,
            ]);
        }

        session()->flash('success', 'Welcome! Your account has been set up successfully.');

        return redirect()->route('student.dashboard');
    }

    public function previousStep()
    {
        if ($this->step > 1) {
            $this->step--;
        }
    }

    public function render()
    {
        return view('livewire.onboarding.student-onboarding');
    }
}
