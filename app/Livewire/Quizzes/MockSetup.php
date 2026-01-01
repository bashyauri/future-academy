<?php

namespace App\Livewire\Quizzes;

use App\Models\ExamType;
use App\Models\MockSession;
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
        // Don't preselect exam type - let user choose
        $this->examTypeId = null;

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
        $examFormat = $examType?->exam_format ?? 'default';

        // Use exam format field instead of name detection
        $isJamb = $examFormat === 'jamb';
        $isSsce = $examFormat === 'ssce';

        // Auto-calculate question counts and time limits based on exam type
        $questionsPerSubject = [];
        $totalTime = 0;

        foreach ($this->selectedSubjects as $subjectId) {
            $subject = Subject::find($subjectId);
            $subjectName = strtolower($subject?->name ?? '');

            if ($isJamb) {
                // JAMB: English = 70, others = 50, total time = 100 mins
                $questionCount = str_contains($subjectName, 'english') ? 70 : 50;
                $questionsPerSubject[$subjectId] = $questionCount;
            } elseif ($isSsce) {
                // SSCE (WAEC/NECO/NAPTEB):
                // English = 110 questions, 50 minutes
                // Maths/Further Maths = 60 questions, 50 minutes
                // All others = 60 questions, 35 minutes
                if (str_contains($subjectName, 'english')) {
                    $questionCount = 110;
                    $totalTime += 50;
                } elseif (str_contains($subjectName, 'math') || str_contains($subjectName, 'further')) {
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

            // Verify availability of mock questions only
            $available = Question::where('exam_type_id', $this->examTypeId)
                ->where('subject_id', $subjectId)
                ->where('is_mock', true)
                ->where('is_active', true)
                ->where('status', 'approved')
                ->count();


            if ($available < $questionCount) {
                $name = $subject?->name ?? 'Subject';
                $this->addError('selectedSubjects', "Not enough mock questions for {$name}. Needed {$questionCount}, available {$available}. Try another subject combination.");
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

        // Create secure mock session in database
        $session = MockSession::create([
            'user_id' => auth()->id(),
            'exam_type_id' => $this->examTypeId,
            'subject_ids' => $this->selectedSubjects,
            'questions_per_subject' => $questionsPerSubject,
            'time_limit' => $timeLimit,
            'selected_year' => null,
            'shuffle' => false,
            'status' => 'active',
            'expires_at' => now()->addHours(24), // Expire after 24 hours
        ]);

        // Redirect with only session ID - secure and clean URL
        return redirect()->route('mock.quiz', ['session' => $session->id]);
    }

    public function render()
    {
        $examTypes = ExamType::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        // Determine current exam type category
        $currentExamType = ExamType::find($this->examTypeId);
        $examFormat = $currentExamType?->exam_format ?? 'default';

        $isJamb = $examFormat === 'jamb';
        $isSsce = $examFormat === 'ssce';

        return view('livewire.quizzes.mock-setup', [
            'examTypes' => $examTypes,
            'isJamb' => $isJamb,
            'isSsce' => $isSsce,
        ]);
    }
}
