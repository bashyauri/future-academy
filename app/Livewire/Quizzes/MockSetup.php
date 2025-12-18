<?php

namespace App\Livewire\Quizzes;

use App\Models\ExamType;
use App\Models\Question;
use App\Models\Subject;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class MockSetup extends Component
{
    public ?int $examTypeId = null;
    public ?int $selectedYear = null;
    public array $selectedSubjects = [];
    public $subjects;
    public $years;

    public int $questionsPerSubject = 40;
    public int $timeLimit = 180; // minutes
    public bool $shuffleQuestions = true;
    public bool $showAnswersImmediately = false;
    public bool $showExplanations = false;

    public int $maxSubjects = 4;

    public function mount(): void
    {
        $defaultExamType = ExamType::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->first();

        $this->examTypeId = $defaultExamType?->id;

        $this->loadOptions();
    }

    public function updatedExamTypeId(): void
    {
        $this->selectedSubjects = [];
        $this->selectedYear = null;
        $this->loadOptions();
    }

    public function loadOptions(): void
    {
        if (!$this->examTypeId) {
            $this->subjects = collect();
            $this->years = collect();
            return;
        }

        $this->subjects = Subject::where('is_active', true)
            ->whereHas('questions', function ($query) {
                $query->where('exam_type_id', $this->examTypeId)
                    ->where('is_active', true)
                    ->where('status', 'approved');
            })
            ->orderBy('name')
            ->get();

        $this->years = Question::where('exam_type_id', $this->examTypeId)
            ->where('is_active', true)
            ->where('status', 'approved')
            ->distinct()
            ->orderByDesc('exam_year')
            ->pluck('exam_year')
            ->filter()
            ->unique()
            ->values();

        if (!$this->selectedYear && $this->years->isNotEmpty()) {
            $this->selectedYear = $this->years->first();
        }
    }

    public function toggleSubject(int $subjectId): void
    {
        if (in_array($subjectId, $this->selectedSubjects, true)) {
            $this->selectedSubjects = array_values(array_diff($this->selectedSubjects, [$subjectId]));
            return;
        }

        if (count($this->selectedSubjects) < $this->maxSubjects) {
            $this->selectedSubjects[] = $subjectId;
        }
    }

    public function startMock()
    {
        $this->validate([
            'examTypeId' => 'required|exists:exam_types,id',
            'selectedYear' => 'required',
            'selectedSubjects' => 'required|array|min:1|max:' . $this->maxSubjects,
            'questionsPerSubject' => 'required|integer|min:5|max:100',
            'timeLimit' => 'required|integer|min:10|max:600',
        ]);

        // Verify availability per subject
        foreach ($this->selectedSubjects as $subjectId) {
            $available = Question::where('exam_type_id', $this->examTypeId)
                ->where('subject_id', $subjectId)
                ->when($this->selectedYear, fn($q) => $q->where('exam_year', $this->selectedYear))
                ->where('is_active', true)
                ->where('status', 'approved')
                ->count();

            if ($available < $this->questionsPerSubject) {
                $subject = Subject::find($subjectId);
                $name = $subject?->name ?? 'Subject';
                $this->addError('selectedSubjects', "Not enough questions for {$name}. Needed {$this->questionsPerSubject}, available {$available}.");
                return;
            }
        }

        return redirect()->route('mock.quiz', [
            'examType' => $this->examTypeId,
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
        $examTypes = ExamType::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('livewire.quizzes.mock-setup', [
            'examTypes' => $examTypes,
        ]);
    }
}
