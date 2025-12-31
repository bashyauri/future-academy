<?php

namespace App\Livewire\Practice;

use App\Models\ExamType;
use App\Models\Question;
use App\Models\Subject;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

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
    public $resumeAttempt = null;
    public $allResumeAttempts = [];

    public function mount()
    {
        // Load all subjects with questions
        $this->subjects = Subject::where('is_active', true)
            ->whereHas('questions', function ($query) {
                $query->where('is_active', true)
                    ->where('status', 'approved');
            })
            ->orderBy('name')
            ->get();

        // Load all exam types (optional, can be filtered by subject if needed)
        $this->examTypes = ExamType::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        // Load all in-progress attempts for the user
        $this->allResumeAttempts = [];
        if (Auth::check()) {
            $this->allResumeAttempts = \App\Models\QuizAttempt::where('user_id', Auth::id())
                ->where('status', 'in_progress')
                ->orderByDesc('created_at')
                ->get()
                ->filter(function ($attempt) {
                    // If the quiz is timed and time has expired, do not show
                    if ($attempt->time_taken_seconds && $attempt->time_taken_seconds > 0 && $attempt->time_taken_seconds <= now()->diffInSeconds($attempt->started_at)) {
                        return false;
                    }
                    if ($attempt->started_at && $attempt->time_taken_seconds) {
                        $elapsed = now()->diffInSeconds($attempt->started_at);
                        if ($attempt->time_taken_seconds > 0 && $elapsed >= $attempt->time_taken_seconds) {
                            return false;
                        }
                    }
                    return true;
                })
                ->values();
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
        // Check for in-progress attempt for resume
        $this->resumeAttempt = null;
        if (Auth::check() && $this->selectedExamType) {
            $query = \App\Models\QuizAttempt::where('user_id', Auth::id())
                ->where('exam_type_id', $this->selectedExamType)
                ->where('status', 'in_progress');
            if ($this->selectedYear) {
                $query->where('exam_year', $this->selectedYear);
            }
            $this->resumeAttempt = $query->latest('created_at')->first();
        }
    }

    public function startPractice()
    {
        $this->validate([
            'selectedSubject' => 'required',
        ], [
            'selectedSubject.required' => 'Please select a subject',
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

    public function resumePractice()
    {
        if ($this->resumeAttempt) {
            return redirect()->route('practice.quiz', [
                'exam_type' => $this->selectedExamType,
                'subject' => $this->selectedSubject,
                'year' => $this->selectedYear,
                'attempt' => $this->resumeAttempt->id,
            ]);
        }
    }

    public function dismissResumeAttempt($attemptId)
    {
        $attempt = \App\Models\QuizAttempt::where('id', $attemptId)
            ->where('user_id', Auth::id())
            ->where('status', 'in_progress')
            ->first();
        if ($attempt) {
            $attempt->delete();
            // Refresh the list
            $this->allResumeAttempts = $this->allResumeAttempts->filter(fn($a) => $a->id !== $attemptId)->values();
        }
    }

    public function render()
    {
        return view('livewire.practice.practice-home', [
            'resumeAttempt' => $this->resumeAttempt,
            'allResumeAttempts' => $this->allResumeAttempts,
        ]);
    }
}

