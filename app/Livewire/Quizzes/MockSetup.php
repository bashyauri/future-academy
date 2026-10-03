<?php

namespace App\Livewire\Quizzes;

use App\Models\ExamType;
use App\Models\MockGroup;
use App\Models\MockSession;
use App\Models\Question;
use App\Models\QuizAttempt;
use App\Models\Subject;
use App\Services\MockGroupService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class MockSetup extends Component
{
    public ?int $examTypeId = null;

    public ?int $selectedYear = null;

    public array $selectedSubjects = [];

    public $subjects;

    public ?int $maxSubjects = null;

    public bool $shuffle = true;

    public function mount(): void
    {
        $user = auth()->user();
        if ($user && $user->isStudent() && ! $user->has_completed_onboarding) {
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
        $examType = $this->examTypeId ? ExamType::find($this->examTypeId) : null;
        $this->maxSubjects = self::subjectLimitForFormat($examType?->exam_format);
        $this->loadOptions();
    }

    public static function subjectLimitForFormat(?string $examFormat): ?int
    {
        return strtolower((string) $examFormat) === 'jamb' ? 4 : null;
    }

    public function loadOptions(): void
    {
        $user = Auth::user();
        $selectedSubjectIds = $user?->selected_subjects ?? [];

        if ($user && $user->isStudent() && (empty($selectedSubjectIds) || ! $user->has_completed_onboarding)) {
            $this->redirectRoute('onboarding');

            return;
        }

        if (! $this->examTypeId) {
            $this->subjects = collect();

            return;
        }

        $this->subjects = Subject::query()
            ->where('is_active', true)
            ->when(! empty($selectedSubjectIds), fn ($q) => $q->whereIn('id', $selectedSubjectIds))
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
        if (! $examType) {
            return;
        }

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
            $existingGroups = MockGroup::where('subject_id', $subject->id)
                ->where('exam_type_id', $this->examTypeId)
                ->count();

            // Only group if no groups exist
            if ($existingGroups === 0) {
                // Get batch size from config for this subject
                $specification = $mockGroupService->getSubjectMockSpecification($examType, $subject);
                $mockGroupService->groupMockQuestions($subject, $examType, $specification['questions']);
            }
        }
    }

    public function toggleSubject(int $subjectId): void
    {
        if (in_array($subjectId, $this->selectedSubjects, true)) {
            $this->selectedSubjects = array_values(array_diff($this->selectedSubjects, [$subjectId]));

            return;
        }

        if ($this->maxSubjects === null || count($this->selectedSubjects) < $this->maxSubjects) {
            $this->selectedSubjects[] = $subjectId;
        }
    }

    public function selectSingleSubject(int $subjectId)
    {
        // For single subject, check if mock groups are available
        $mockGroups = MockGroup::where('subject_id', $subjectId)
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

        $subjectRules = ['required', 'array', 'min:1'];
        if ($this->maxSubjects !== null) {
            $subjectRules[] = 'max:'.$this->maxSubjects;
        }

        $this->validate([
            'examTypeId' => 'required|exists:exam_types,id',
            'selectedSubjects' => $subjectRules,
        ]);

        // Get exam type to determine specifications
        $examType = ExamType::find($this->examTypeId);

        // Config-driven question counts and time limits
        $questionsPerSubject = [];
        $mockGroupService = app(MockGroupService::class);

        foreach ($this->selectedSubjects as $subjectId) {
            $subject = Subject::find($subjectId);
            $specification = $examType && $subject
                ? $mockGroupService->getSubjectMockSpecification($examType, $subject)
                : ['questions' => 50, 'time' => null];
            $questionCount = $specification['questions'];
            $questionsPerSubject[$subjectId] = $questionCount;

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

        // Compute time limit from the same format resolver used by the API.
        $timeLimit = $examType
            ? $mockGroupService->getFullMockDuration(
                $examType,
                Subject::whereIn('id', $this->selectedSubjects)->get()
            )
            : 100;

        // Calculate total questions
        $totalQuestions = array_sum($questionsPerSubject);

        // Get all question IDs for this session (will be loaded in MockQuiz)
        // We need to create a flat question order array similar to the API
        $questionOrder = [];
        foreach ($this->selectedSubjects as $subjectId) {
            $query = Question::where('exam_type_id', $this->examTypeId)
                ->where('subject_id', $subjectId)
                ->where('is_mock', true)
                ->when($this->selectedYear, fn ($q) => $q->where('exam_year', $this->selectedYear))
                ->where('is_active', true)
                ->where('status', 'approved')
                ->limit($questionsPerSubject[$subjectId] ?? 40);

            $subjectQuestions = $query->pluck('id')->toArray();
            if ($this->shuffle) {
                shuffle($subjectQuestions);
            }
            $questionOrder = array_merge($questionOrder, $subjectQuestions);
        }

        // Create QuizAttempt first (needed for answer storage)
        $attempt = QuizAttempt::create([
            'user_id' => auth()->id(),
            'exam_type_id' => $this->examTypeId,
            'subject_id' => $this->selectedSubjects[0] ?? null,
            'mock_group_id' => null,
            'exam_year' => null,
            'score' => 0,
            'total_questions' => $totalQuestions,
            'correct_answers' => 0,
            'percentage' => 0,
            'score_percentage' => 0,
            'time_taken_seconds' => 0,
            'started_at' => now(),
            'status' => 'in_progress',
            'current_question_index' => 0,
            'question_order' => $questionOrder,
        ]);

        // Create secure mock session in database with quiz_attempt_id
        $session = MockSession::create([
            'user_id' => auth()->id(),
            'exam_type_id' => $this->examTypeId,
            'quiz_attempt_id' => $attempt->id,
            'mock_group_id' => null,
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

        // Build subject specs from config for display
        $subjectSpecs = [];
        if ($this->subjects && count($this->subjects) > 0) {
            foreach ($this->subjects as $subject) {
                $specification = $currentExamType
                    ? app(MockGroupService::class)->getSubjectMockSpecification($currentExamType, $subject)
                    : ['questions' => 50, 'time' => null];
                $subjectSpecs[$subject->id] = [
                    'questions' => $specification['questions'],
                    'time' => $specification['time'],
                ];
            }
        }

        // Get config specs for sidebar display
        $formats = config('mock.formats', []);
        $formatConfig = $formats[$examFormat] ?? $formats['default'] ?? [];

        $configSpecs = [
            'exam_format' => $examFormat,
            'overall' => $formatConfig['overall'] ?? [],
            'per_subject' => $formatConfig['per_subject'] ?? [],
            'default' => $formatConfig['default'] ?? ['questions' => 50, 'time' => null],
        ];

        return view('livewire.quizzes.mock-setup', [
            'examTypes' => $examTypes,
            'examFormat' => $examFormat,
            'subjectSpecs' => $subjectSpecs,
            'configSpecs' => $configSpecs,
        ]);
    }
}
