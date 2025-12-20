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
    public array $selectedSubjects = [];
    public $subjects;

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
        $this->loadOptions();
    }

    public function loadOptions(): void
    {
        if (!$this->examTypeId) {
            $this->subjects = collect();
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
            'selectedSubjects' => 'required|array|min:1|max:' . $this->maxSubjects,
        ]);

        // Get exam type to determine specifications
        $examType = ExamType::find($this->examTypeId);
        $examTypeName = strtoupper($examType?->name ?? '');

        // Check if this is JAMB or SSCE/WAEC/NECO
        $isJamb = stripos($examTypeName, 'JAMB') !== false;
        $isSsce = stripos($examTypeName, 'WAEC') !== false ||
                  stripos($examTypeName, 'NECO') !== false ||
                  stripos($examTypeName, 'NAPTEB') !== false ||
                  stripos($examTypeName, 'SSCE') !== false;

        // Auto-calculate question counts and time limits based on exam type
        $questionsPerSubject = [];
        $totalTime = 0;

        foreach ($this->selectedSubjects as $subjectId) {
            $subject = Subject::find($subjectId);
            $subjectName = $subject?->name ?? '';

            if ($isJamb) {
                // JAMB: English = 70, others = 50, total time = 100 mins
                $questionCount = (stripos($subjectName, 'English') !== false) ? 70 : 50;
                $questionsPerSubject[$subjectId] = $questionCount;
            } elseif ($isSsce) {
                // SSCE (WAEC/NECO/NAPTEB):
                // English = 110 questions, 50 minutes
                // Maths/Further Maths = 60 questions, 50 minutes
                // All others = 60 questions, 35 minutes
                if (stripos($subjectName, 'English') !== false) {
                    $questionCount = 110;
                    $totalTime += 50;
                } elseif (stripos($subjectName, 'Math') !== false || stripos($subjectName, 'Further') !== false) {
                    $questionCount = 60;
                    $totalTime += 50;
                } else {
                    $questionCount = 60;
                    $totalTime += 35;
                }
                $questionsPerSubject[$subjectId] = $questionCount;
            } else {
                // Default fallback
                $questionCount = 50;
                $questionsPerSubject[$subjectId] = $questionCount;
            }

            // Verify availability (mixed years)
            $available = Question::where('exam_type_id', $this->examTypeId)
                ->where('subject_id', $subjectId)
                ->where('is_active', true)
                ->where('status', 'approved')
                ->count();

            if ($available < $questionCount) {
                $name = $subject?->name ?? 'Subject';
                $this->addError('selectedSubjects', "Not enough questions for {$name}. Needed {$questionCount}, available {$available}.");
                return;
            }
        }

        // Set time limit based on exam type
        if ($isJamb) {
            $timeLimit = 100; // 100 minutes total for JAMB
        } elseif ($isSsce) {
            $timeLimit = $totalTime; // Sum of individual subject times for SSCE
        } else {
            $timeLimit = 100; // Default
        }

        return redirect()->route('mock.quiz', [
            'examType' => $this->examTypeId,
            'subjects' => implode(',', $this->selectedSubjects),
            'questionsPerSubject' => json_encode($questionsPerSubject),
            'timeLimit' => $timeLimit,
            'showAnswers' => '0',
            'showExplanations' => '0',
            'shuffle' => '0',
        ]);
    }

    public function render()
    {
        $examTypes = ExamType::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        // Determine current exam type category
        $currentExamType = ExamType::find($this->examTypeId);
        $examTypeName = strtoupper($currentExamType?->name ?? '');

        $isJamb = stripos($examTypeName, 'JAMB') !== false;
        $isSsce = stripos($examTypeName, 'WAEC') !== false ||
                  stripos($examTypeName, 'NECO') !== false ||
                  stripos($examTypeName, 'NAPTEB') !== false ||
                  stripos($examTypeName, 'SSCE') !== false;

        return view('livewire.quizzes.mock-setup', [
            'examTypes' => $examTypes,
            'isJamb' => $isJamb,
            'isSsce' => $isSsce,
        ]);
    }
}
