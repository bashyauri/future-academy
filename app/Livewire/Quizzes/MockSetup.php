<?php

namespace App\Livewire\Quizzes;

use App\Models\ExamType;
use App\Models\MockSession;
use App\Models\Question;
use App\Models\Subject;
use App\Services\MockGroupService;
use Illuminate\Support\Facades\Auth;
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
        $user = auth()->user();
        if ($user && $user->isStudent() && !$user->has_completed_onboarding) {
            $this->redirectRoute('onboarding');
            return;
        }

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
        $user = Auth::user();
        $selectedSubjectIds = $user?->selected_subjects ?? [];

        if ($user && $user->isStudent() && (empty($selectedSubjectIds) || !$user->has_completed_onboarding)) {
            $this->redirectRoute('onboarding');
            return;
        }

        if (!$this->examTypeId) {
            $this->subjects = collect();
            return;
        }

        $this->subjects = Subject::query()
            ->where('is_active', true)
            ->when(!empty($selectedSubjectIds), fn($q) => $q->whereIn('id', $selectedSubjectIds))
            ->whereHas('questions', function ($query) {
                $query->where('exam_type_id', $this->examTypeId)
                    ->where('is_active', true)
                    ->where('status', 'approved');
            })
            ->orderBy('name')
            ->get();

        // Auto-group mock questions when exam type is selected
        if ($this->examTypeId) {
            $this->autoGroupMockQuestions();
        }
    }

    /**
     * Automatically group mock questions for all subjects in the selected exam type.
     * This runs on page load so shared hosting users don't need command line access.
     */
    protected function autoGroupMockQuestions(): void
    {
        $examType = ExamType::find($this->examTypeId);
        if (!$examType) {
            return;
        }

        $examFormat = $examType->exam_format ?? 'default';
        $mockGroupService = app(MockGroupService::class);

        // Get all subjects that have mock questions for this exam type
        $subjectsWithMocks = Subject::whereHas('questions', function ($query) {
            $query->where('exam_type_id', $this->examTypeId)
                ->where('is_mock', true)
                ->where('is_active', true)
                ->where('status', 'approved');
        })->get();

        foreach ($subjectsWithMocks as $subject) {
            // Check if groups already exist
            $existingGroups = \App\Models\MockGroup::where('subject_id', $subject->id)
                ->where('exam_type_id', $this->examTypeId)
                ->count();

            // Only group if no groups exist
            if ($existingGroups === 0) {
                // Get batch size from config for this subject
                [$batchSize] = $this->getSubjectSpec($examFormat, strtolower($subject->name));
                $mockGroupService->groupMockQuestions($subject, $examType, $batchSize);
            }
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

    public function selectSingleSubject(int $subjectId)
    {
        // For single subject, check if mock groups are available
        $mockGroups = \App\Models\MockGroup::where('subject_id', $subjectId)
            ->where('exam_type_id', $this->examTypeId)
            ->exists();

        if ($mockGroups) {
            // Redirect to mock group selection
            return redirect()->route('mock.group-selection', [
                'exam_type' => $this->examTypeId,
                'subject' => $subjectId,
            ]);
        }

        // Fallback: select normally and continue
        $this->selectedSubjects = [$subjectId];
        return $this->startMock();
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

        // Config-driven question counts and time limits
        $questionsPerSubject = [];
        $perSubjectTimes = [];

        foreach ($this->selectedSubjects as $subjectId) {
            $subject = Subject::find($subjectId);
            $subjectName = strtolower($subject?->name ?? '');

            [$questionCount, $subjectTime] = $this->getSubjectSpec($examFormat, $subjectName);
            $questionsPerSubject[$subjectId] = $questionCount;
            if (!is_null($subjectTime)) {
                $perSubjectTimes[] = $subjectTime;
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

        // Compute time limit from config
        $timeLimit = $this->computeTimeLimit($examFormat, $perSubjectTimes);

        // Create secure mock session in database
        $session = MockSession::create([
            'user_id' => auth()->id(),
            'exam_type_id' => $this->examTypeId,
            'subject_ids' => $this->selectedSubjects,
            'questions_per_subject' => $questionsPerSubject,
            'time_limit' => $timeLimit,
            'selected_year' => null,
            'shuffle' => true,
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

    /**
     * Resolve per-subject question counts and time from config.
     */
    protected function getSubjectSpec(string $examFormat, string $subjectName): array
    {
        $formats = config('mock.formats', []);
        $format = $formats[$examFormat] ?? $formats['default'] ?? [];

        $default = $format['default'] ?? ['questions' => 50, 'time' => null];
        $spec = $default;

        foreach (($format['per_subject'] ?? []) as $rule) {
            foreach (($rule['match'] ?? []) as $needle) {
                if ($needle && str_contains($subjectName, strtolower($needle))) {
                    $spec = [
                        'questions' => $rule['questions'] ?? $default['questions'],
                        'time' => $rule['time'] ?? $default['time'],
                    ];
                    break 2;
                }
            }
        }

        return [
            (int) ($spec['questions'] ?? 50),
            $spec['time'] ?? null,
        ];
    }

    /**
     * Compute overall time limit for the mock from config.
     */
    protected function computeTimeLimit(string $examFormat, array $perSubjectTimes): int
    {
        $formats = config('mock.formats', []);
        $format = $formats[$examFormat] ?? $formats['default'] ?? [];
        $overall = $format['overall'] ?? [];

        if (isset($overall['time_limit'])) {
            return (int) $overall['time_limit'];
        }

        if (!empty($overall['sum_subject_time'])) {
            return array_sum(array_map('intval', $perSubjectTimes)) ?: 100;
        }

        return 100; // fallback
    }
}
