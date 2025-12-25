<?php

namespace App\Livewire\Practice;

use App\Models\ExamType;
use App\Models\Question;
use App\Models\Subject;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class PracticeHome extends Component
{
    public $selectedExamType = null;
    public $selectedSubject = null;
    public $selectedYear = null;
    public $shuffleQuestions = false;
    public $questionLimit = null; // null means all questions
    public $timeLimit = null; // null means no time limit

    public $examTypes = [];
    public $subjects = [];
    public $filteredYears = [];
    public $availableQuestionCount = 0;

    public function mount()
    {
        // Load exam types that have questions
        $this->examTypes = ExamType::where('is_active', true)
            ->whereHas('questions', function ($query) {
                $query->where('is_active', true)
                    ->where('status', 'approved');
            })
            ->orderBy('sort_order')
            ->get();

        // Initialize subjects as empty array
        $this->subjects = [];
    }

    public function updatedSelectedExamType()
    {
        // Reset dependent fields
        $this->selectedSubject = null;
        $this->selectedYear = null;
        $this->filteredYears = [];

        if ($this->selectedExamType) {
            // Load subjects that have questions for selected exam type
            $this->subjects = Subject::where('is_active', true)
                ->whereHas('questions', function ($query) {
                    if ($this->selectedExamType) {
                        $query->where('exam_type_id', $this->selectedExamType);
                    }
                    $query->where('is_active', true)
                        ->where('status', 'approved');
                })
                ->orderBy('name')
                ->get();
        } else {
            // All exam types: show subjects with any active, approved questions
            $this->subjects = Subject::where('is_active', true)
                ->whereHas('questions', function ($query) {
                    $query->where('is_active', true)
                        ->where('status', 'approved');
                })
                ->orderBy('name')
                ->get();
        }
    }

    public function updatedSelectedSubject()
    {
        // Reset dependent field
        $this->selectedYear = null;
        $this->availableQuestionCount = 0;

        if ($this->selectedExamType && $this->selectedSubject) {
            // Filter years available for selected exam type and subject
            $years = Question::where('subject_id', $this->selectedSubject)
                ->when($this->selectedExamType, function ($query) {
                    $query->where('exam_type_id', $this->selectedExamType);
                })
                ->where('is_active', true)
                ->where('status', 'approved')
                ->get()
                ->map(function ($q) {
                    return $q->exam_year ?: $q->year;
                })
                ->filter()
                ->unique()
                ->sortDesc()
                ->values();
            $this->filteredYears = $years;
        } else {
            $this->filteredYears = [];
        }
    }

    public function updatedSelectedYear()
    {
        // Update available question count when year is selected
        if ($this->selectedExamType && $this->selectedSubject && $this->selectedYear) {
            $this->availableQuestionCount = Question::where('exam_type_id', $this->selectedExamType)
                ->where('subject_id', $this->selectedSubject)
                ->where('exam_year', $this->selectedYear)
                ->where('is_active', true)
                ->where('status', 'approved')
                ->count();
        } else {
            $this->availableQuestionCount = 0;
        }
    }

    public function startPractice()
    {
        $this->validate([
            'selectedExamType' => 'required',
            'selectedSubject' => 'required',
            // 'selectedYear' => 'required', // Remove required for year
        ], [
            'selectedExamType.required' => 'Please select an exam type',
            'selectedSubject.required' => 'Please select a subject',
            // 'selectedYear.required' => 'Please select a year',
        ]);

        // Verify that questions exist for this combination
        $questionQuery = Question::query()
            ->where('subject_id', $this->selectedSubject)
            ->when($this->selectedExamType, function ($query) {
                $query->where('exam_type_id', $this->selectedExamType);
            })
            ->where('is_active', true)
            ->where('status', 'approved');
        if ($this->selectedYear) {
            $questionQuery->where(function ($q) {
                $q->where('exam_year', $this->selectedYear)
                  ->orWhere(function ($sub) {
                      $sub->whereNull('exam_year')->where('year', $this->selectedYear);
                  });
            });
        }
        $questionCount = $questionQuery->count();

        if ($questionCount === 0) {
            $this->addError('selectedYear', 'No questions available for this combination. Please select a different option.');
            return;
        }

        // Validate question limit if set
        if ($this->questionLimit && $this->questionLimit > $questionCount) {
            $this->addError('questionLimit', "Only {$questionCount} questions available for this combination.");
            return;
        }

        // Redirect to practice quiz with parameters
        return redirect()->route('practice.quiz', [
            'exam_type' => $this->selectedExamType,
            'subject' => $this->selectedSubject,
            'year' => $this->selectedYear,
            'shuffle' => $this->shuffleQuestions ? '1' : '0',
            'limit' => $this->questionLimit,
            'time' => $this->timeLimit,
        ]);
    }

    public function render()
    {
        return view('livewire.practice.practice-home');
    }
}

