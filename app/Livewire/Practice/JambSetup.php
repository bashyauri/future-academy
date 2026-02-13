<?php

namespace App\Livewire\Practice;

use App\Models\ExamType;
use App\Models\Subject;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class JambSetup extends Component
{
    public $examType = 'jamb';
    public $selectedYear = null;
    public $selectedSubjects = [];
    public $subjects = [];
    public $years = [];
    public $shuffleQuestions = false;
    public $maxSubjects = 4;
    public $questionsPerSubject = null; // null means all available
    public $timeLimit = null; // null means no time limit

    public function mount()
    {
        // Get JAMB exam type
        $jambExamType = ExamType::where('slug', 'jamb')->first();

        if ($jambExamType) {
            $this->examType = $jambExamType->id;

            // Load only subjects that have JAMB questions
            $this->subjects = Subject::where('is_active', true)
                ->whereHas('questions', function ($query) use ($jambExamType) {
                    $query->where('exam_type_id', $jambExamType->id)
                        ->where('is_active', true)
                        ->where('status', 'approved');
                })
                ->orderBy('name')
                ->get();

            // Get available years for JAMB
            $this->years = \App\Models\Question::where('exam_type_id', $jambExamType->id)
                ->where('is_active', true)
                ->where('status', 'approved')
                ->distinct()
                ->orderByDesc('exam_year')
                ->pluck('exam_year')
                ->filter()
                ->unique()
                ->values();
        }
    }

    public function toggleSubject($subjectId)
    {
        if (in_array($subjectId, $this->selectedSubjects)) {
            $this->selectedSubjects = array_values(array_diff($this->selectedSubjects, [$subjectId]));
        } else {
            if (count($this->selectedSubjects) < $this->maxSubjects) {
                $this->selectedSubjects[] = $subjectId;
            }
        }
    }

    public function startJambTest()
    {
        // Default values for JAMB practice if not customized
        $questionsPerSubject = $this->questionsPerSubject ?? 40; // Standard JAMB questions per subject
        $timeLimit = $this->timeLimit; // Can be null for unlimited time

        $this->validate([
            'selectedSubjects' => 'required|array|size:' . $this->maxSubjects,
        ], [
            'selectedSubjects.required' => 'Please select subjects',
            'selectedSubjects.size' => 'You must select exactly ' . $this->maxSubjects . ' subjects for JAMB',
        ]);

        // Verify that each selected subject has enough questions
        foreach ($this->selectedSubjects as $subjectId) {
            $query = \App\Models\Question::where('exam_type_id', $this->examType)
                ->where('subject_id', $subjectId)
                ->where('is_active', true)
                ->where('status', 'approved');

            // Filter by year only if selected
            if ($this->selectedYear) {
                $query->where('exam_year', $this->selectedYear);
            }

            $questionCount = $query->count();

            if ($questionCount < $questionsPerSubject) {
                $subject = Subject::find($subjectId);
                $yearText = $this->selectedYear ? $this->selectedYear : 'all available years';
                $this->addError('selectedSubjects',
                    "Not enough questions for {$subject->name} in {$yearText}. Available: {$questionCount}, Required: {$questionsPerSubject}");
                return;
            }
        }

        return redirect()->route('practice.jamb.quiz', [
            'year' => $this->selectedYear,
            'subjects' => implode(',', $this->selectedSubjects),
            'timeLimit' => $timeLimit,
            'questionsPerSubject' => $questionsPerSubject,
            'shuffle' => $this->shuffleQuestions ? '1' : '0',
        ]);
    }

    public function render()
    {
        return view('livewire.practice.jamb-setup');
    }
}

