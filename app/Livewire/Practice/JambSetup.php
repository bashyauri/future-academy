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
    public $questionsPerSubject = 10;
    public $timeLimit = 180; // 3 hours in minutes
    public $timeLimitPerQuestion = 2; // minutes per question
    public $showAnswersImmediately = false;
    public $showExplanations = false;
    public $shuffleQuestions = true;
    public $maxSubjects = 4;

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
        $this->validate([
            'selectedYear' => 'required',
            'selectedSubjects' => 'required|array|size:' . $this->maxSubjects,
            'timeLimit' => 'required|numeric|min:10|max:1000',
            'questionsPerSubject' => 'required|numeric|min:5|max:100',
        ], [
            'selectedYear.required' => 'Please select an exam year',
            'selectedSubjects.required' => 'Please select subjects',
            'selectedSubjects.size' => 'You must select exactly ' . $this->maxSubjects . ' subjects for JAMB',
        ]);

        // Verify that each selected subject has enough questions for the selected year
        foreach ($this->selectedSubjects as $subjectId) {
            $questionCount = \App\Models\Question::where('exam_type_id', $this->examType)
                ->where('subject_id', $subjectId)
                ->where('exam_year', $this->selectedYear)
                ->where('is_active', true)
                ->where('status', 'approved')
                ->count();

            if ($questionCount < $this->questionsPerSubject) {
                $subject = Subject::find($subjectId);
                $this->addError('selectedSubjects', 
                    "Not enough questions for {$subject->name} in {$this->selectedYear}. Available: {$questionCount}, Required: {$this->questionsPerSubject}");
                return;
            }
        }

        return redirect()->route('practice.jamb.quiz', [
            'year' => $this->selectedYear,
            'subjects' => implode(',', $this->selectedSubjects),
            'timeLimit' => $this->timeLimit,
            'questionsPerSubject' => $this->questionsPerSubject,
            'showAnswers' => $this->showAnswersImmediately ? '1' : '0',
            'showExplanations' => $this->showExplanations ? '1' : '0',
            'shuffle' => $this->shuffleQuestions ? '1' : '0',
        ]);
    }

    public function render()
    {
        return view('livewire.practice.jamb-setup');
    }
}

