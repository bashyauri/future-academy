<?php

namespace App\Livewire\Quizzes;

use App\Models\ExamType;
use App\Models\MockGroup;
use App\Models\Subject;
use App\Services\MockGroupService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class MockGroupSelection extends Component
{
    public ?int $examTypeId = null;
    public ?int $subjectId = null;
    public ?string $subjectName = null;
    public ?string $examTypeName = null;
    public array $mockGroups = [];
    public array $completedMockGroupIds = [];
    public array $bestScores = [];
    public ?int $selectedBatchNumber = null;

    // Rate limiting and throttling
    private const MAX_BATCH_SELECTIONS_PER_MINUTE = 10;

    public function mount()
    {
        // Get from request query parameters with validation
        $this->examTypeId = (int) request()->query('exam_type', 0) ?: null;
        $this->subjectId = (int) request()->query('subject', 0) ?: null;

        // Clear completion cache on fresh load to show latest completions
        $cacheKey = "user_{$this->getUserId()}_completed_mocks_{$this->examTypeId}_{$this->subjectId}";
        cache()->forget($cacheKey);

        // Validate required parameters
        if (!$this->examTypeId || !$this->subjectId) {
            Log::warning('MockGroupSelection: Missing required parameters', [
                'user_id' => auth()->id(),
                'exam_type' => request()->query('exam_type'),
                'subject' => request()->query('subject'),
            ]);
            session()->flash('error', 'Invalid parameters. Please select exam type and subject first.');
            return redirect()->route('mock.setup');
        }

        // Verify subject and exam type exist and are active
        try {
            $subject = Subject::where('id', $this->subjectId)
                ->where('is_active', true)
                ->firstOrFail();

            $examType = ExamType::where('id', $this->examTypeId)
                ->where('is_active', true)
                ->firstOrFail();

            // Store names for display
            $this->subjectName = $subject->name;
            $this->examTypeName = $examType->name;
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning('MockGroupSelection: Invalid subject or exam type', [
                'user_id' => auth()->id(),
                'subject_id' => $this->subjectId,
                'exam_type_id' => $this->examTypeId,
            ]);
            session()->flash('error', 'Subject or exam type not found or is inactive.');
            return redirect()->route('mock.setup');
        }

        $this->loadMockGroups();
    }

    protected function loadMockGroups()
    {
        try {
            $mockGroupService = app(MockGroupService::class);
            $subject = Subject::findOrFail($this->subjectId);
            $examType = ExamType::findOrFail($this->examTypeId);

            $groups = $mockGroupService->getMockGroups($subject, $examType);

            if ($groups->isEmpty()) {
                Log::info('MockGroupSelection: No mock groups available', [
                    'user_id' => auth()->id(),
                    'subject_id' => $this->subjectId,
                    'exam_type_id' => $this->examTypeId,
                ]);
                session()->flash('warning', 'No mock questions available for the selected subject and exam type.');
                return redirect()->route('mock.setup');
            }

            // Load completed mock groups for this user with caching
            $this->loadCompletedMocks($groups);

            // Map groups with security considerations
            $this->mockGroups = $groups->map(fn($group) => [
                'id' => (int) $group->id, // Ensure ID is integer
                'batch_number' => (int) $group->batch_number,
                'total_questions' => (int) $group->total_questions,
                'label' => "Mock {$group->batch_number}", // Safe HTML - no user input
                'isCompleted' => in_array($group->id, $this->completedMockGroupIds, true),
                'bestScore' => $this->bestScores[$group->id] ?? null,
            ])->toArray();

            Log::info('MockGroupSelection: Groups loaded successfully', [
                'user_id' => auth()->id(),
                'group_count' => count($this->mockGroups),
            ]);
        } catch (\Exception $e) {
            Log::error('MockGroupSelection: Error loading mock groups', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);
            session()->flash('error', 'An error occurred. Please try again.');
            redirect()->route('mock.setup');
        }
    }

    protected function loadCompletedMocks($groups)
    {
        $groupIds = $groups->pluck('id')->toArray();

        if (empty($groupIds)) {
            return;
        }

        try {
            // Directly query quiz_attempts by mock_group_id (fast + reliable)
            $completedAttempts = DB::table('quiz_attempts')
                ->whereIn('mock_group_id', $groupIds)
                ->where('user_id', auth()->id())
                ->where('status', 'completed')
                ->select('mock_group_id', DB::raw('MAX(percentage) as best_percentage'))
                ->groupBy('mock_group_id')
                ->get();

            // Debug logging
            Log::info('MockGroupSelection: Query results', [
                'user_id' => auth()->id(),
                'group_ids' => $groupIds,
                'exam_type_id' => $this->examTypeId,
                'subject_id' => $this->subjectId,
                'results_count' => count($completedAttempts),
                'results' => $completedAttempts->toArray(),
            ]);

            foreach ($completedAttempts as $attempt) {
                $this->completedMockGroupIds[] = (int) $attempt->mock_group_id;
                $this->bestScores[(int) $attempt->mock_group_id] = (float) $attempt->best_percentage;
            }

            Log::info('MockGroupSelection: Completed mocks loaded', [
                'user_id' => auth()->id(),
                'completed_count' => count($this->completedMockGroupIds),
                'completed_ids' => $this->completedMockGroupIds,
            ]);
        } catch (\Exception $e) {
            Log::error('MockGroupSelection: Error loading completed mocks', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            // Don't crash - just show no completed mocks
        }
    }

    public function selectBatch($batchNumber)
    {
        // Validate batch number is a positive integer
        $batchNumber = (int) $batchNumber;
        if ($batchNumber <= 0) {
            session()->flash('error', 'Invalid batch number.');
            Log::warning('MockGroupSelection: Invalid batch number', [
                'user_id' => auth()->id(),
                'batch_number' => $batchNumber,
            ]);
            return;
        }

        // Check rate limiting
        if (!$this->checkRateLimit()) {
            session()->flash('error', 'Too many requests. Please wait a moment.');
            return;
        }

        $this->selectedBatchNumber = $batchNumber;

        try {
            $mockGroupService = app(MockGroupService::class);
            $subject = Subject::findOrFail($this->subjectId);
            $examType = ExamType::findOrFail($this->examTypeId);

            // Verify batch number is valid for this subject/exam combo
            $mockGroup = $mockGroupService->getMockGroupByBatchNumber($subject, $examType, $batchNumber);

            if (!$mockGroup) {
                Log::warning('MockGroupSelection: Mock group not found', [
                    'user_id' => auth()->id(),
                    'subject_id' => $this->subjectId,
                    'exam_type_id' => $this->examTypeId,
                    'batch_number' => $batchNumber,
                ]);
                session()->flash('error', 'Mock group not found.');
                return;
            }

            // Verify questions exist and user hasn't tampered with data
            $questionCount = \App\Models\Question::where('mock_group_id', $mockGroup->id)
                ->where('is_active', true)
                ->where('status', 'approved')
                ->count();

            if ($questionCount === 0) {
                Log::warning('MockGroupSelection: No active questions in mock group', [
                    'user_id' => auth()->id(),
                    'mock_group_id' => $mockGroup->id,
                ]);
                session()->flash('error', 'No active questions available in this mock group.');
                return;
            }

            // Log successful selection
            Log::info('MockGroupSelection: Batch selected', [
                'user_id' => auth()->id(),
                'mock_group_id' => $mockGroup->id,
                'batch_number' => $batchNumber,
            ]);

            // Start the mock quiz with this specific group
            return redirect()->route('mock.quiz', [
                'group' => $mockGroup->id,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning('MockGroupSelection: Subject or exam type not found', [
                'user_id' => auth()->id(),
                'subject_id' => $this->subjectId,
                'exam_type_id' => $this->examTypeId,
            ]);
            session()->flash('error', 'Invalid subject or exam type.');
            return redirect()->route('mock.setup');
        } catch (\Exception $e) {
            Log::error('MockGroupSelection: Error selecting batch', [
                'user_id' => auth()->id(),
                'batch_number' => $batchNumber,
                'error' => $e->getMessage(),
            ]);
            session()->flash('error', 'An error occurred. Please try again.');
        }
    }

    /**
     * Check rate limiting to prevent abuse
     */
    protected function checkRateLimit(): bool
    {
        $key = "mock_batch_selection_{$this->getUserId()}";
        $attempts = cache()->get($key, 0);

        if ($attempts >= self::MAX_BATCH_SELECTIONS_PER_MINUTE) {
            return false;
        }

        cache()->put($key, $attempts + 1, now()->addMinute());
        return true;
    }

    /**
     * Get authenticated user ID safely
     */
    protected function getUserId(): int
    {
        return (int) auth()->id();
    }

    public function render()
    {
        return view('livewire.quizzes.mock-group-selection');
    }
}

