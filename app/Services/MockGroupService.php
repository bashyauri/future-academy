<?php

namespace App\Services;

use App\Models\ExamType;
use App\Models\MockGroup;
use App\Models\Question;
use App\Models\Subject;

class MockGroupService
{
    const DEFAULT_BATCH_SIZE = 40;

    /**
     * Resolve the configured mock question count and time for a subject.
     *
     * @return array{questions: int, time: int|null}
     */
    public function getSubjectMockSpecification(ExamType $examType, Subject $subject): array
    {
        $examTypeFormat = strtolower($examType->exam_format ?? 'default');
        $subjectName = strtolower($subject->name);

        $formats = config('mock.formats', []);
        $formatConfig = $formats[$examTypeFormat] ?? $formats['default'] ?? [];
        $default = $formatConfig['default'] ?? [
            'questions' => self::DEFAULT_BATCH_SIZE,
            'time' => null,
        ];
        $specification = $default;

        foreach (($formatConfig['per_subject'] ?? []) as $rule) {
            foreach (($rule['match'] ?? []) as $pattern) {
                if ($pattern && str_contains($subjectName, strtolower($pattern))) {
                    $specification = [
                        'questions' => $rule['questions'] ?? $default['questions'] ?? self::DEFAULT_BATCH_SIZE,
                        'time' => $rule['time'] ?? $default['time'] ?? null,
                    ];
                    break 2;
                }
            }
        }

        return [
            'questions' => (int) ($specification['questions'] ?? self::DEFAULT_BATCH_SIZE),
            'time' => isset($specification['time']) ? (int) $specification['time'] : null,
        ];
    }

    /**
     * Resolve the overall duration for a full mock from the configured format.
     *
     * @param  iterable<Subject>  $subjects
     */
    public function getFullMockDuration(ExamType $examType, iterable $subjects): int
    {
        $formats = config('mock.formats', []);
        $examTypeFormat = strtolower($examType->exam_format ?? 'default');
        $formatConfig = $formats[$examTypeFormat] ?? $formats['default'] ?? [];
        $overall = $formatConfig['overall'] ?? [];

        if (isset($overall['time_limit'])) {
            return (int) $overall['time_limit'];
        }

        if (! empty($overall['sum_subject_time'])) {
            $subjectTimes = [];

            foreach ($subjects as $subject) {
                $time = $this->getSubjectMockSpecification($examType, $subject)['time'];
                if ($time !== null) {
                    $subjectTimes[] = $time;
                }
            }

            return array_sum($subjectTimes) ?: 100;
        }

        return 100;
    }

    /**
     * Group mock questions for a subject and exam type into batches
     */
    public function groupMockQuestions(
        Subject $subject,
        ExamType $examType,
        ?int $batchSize = null
    ): void {
        // Use config-based batch size if not explicitly provided
        $batchSize = $batchSize ?? $this->getSubjectMockSpecification($examType, $subject)['questions'];
        // Get all mock questions for this subject and exam type, ordered by ID
        $mockQuestions = Question::where('subject_id', $subject->id)
            ->where('exam_type_id', $examType->id)
            ->where('is_mock', true)
            ->where('is_active', true)
            ->where('status', 'approved')
            ->whereHas('options')
            ->orderBy('id')
            ->get();

        if ($mockQuestions->isEmpty()) {
            return;
        }

        // First, unlink all mock questions from their groups (set mock_group_id to null)
        // This prevents cascade deletion of questions when we delete the groups
        Question::where('subject_id', $subject->id)
            ->where('exam_type_id', $examType->id)
            ->where('is_mock', true)
            ->update(['mock_group_id' => null]);

        // Now safely clear existing groups for this subject-exam combo
        MockGroup::where('subject_id', $subject->id)
            ->where('exam_type_id', $examType->id)
            ->delete();

        // Create new groups and assign questions
        $batchNumber = 1;
        $questionsChunked = $mockQuestions->chunk($batchSize);

        foreach ($questionsChunked as $chunk) {
            $mockGroup = MockGroup::create([
                'subject_id' => $subject->id,
                'exam_type_id' => $examType->id,
                'batch_number' => $batchNumber,
                'total_questions' => $chunk->count(),
            ]);

            // Assign questions to this group
            $questionIds = $chunk->pluck('id')->toArray();
            Question::whereIn('id', $questionIds)
                ->update(['mock_group_id' => $mockGroup->id]);

            $batchNumber++;
        }
    }

    /**
     * Get all mock groups for a subject and exam type
     */
    public function getMockGroups(Subject $subject, ExamType $examType): mixed
    {
        return MockGroup::where('subject_id', $subject->id)
            ->where('exam_type_id', $examType->id)
            ->orderBy('batch_number')
            ->get();
    }

    /**
     * Get mock group by batch number
     */
    public function getMockGroupByBatchNumber(Subject $subject, ExamType $examType, int $batchNumber): ?MockGroup
    {
        return MockGroup::where('subject_id', $subject->id)
            ->where('exam_type_id', $examType->id)
            ->where('batch_number', $batchNumber)
            ->first();
    }

    /**
     * Get questions for a mock group
     */
    public function getGroupQuestions(MockGroup $mockGroup): mixed
    {
        return $mockGroup->questions()
            ->where('is_mock', true)
            ->where('is_active', true)
            ->where('status', 'approved')
            ->whereHas('options')
            ->with('subject', 'examType', 'options')
            ->get();
    }

    /**
     * Check if there's a next mock group
     */
    public function hasNextGroup(MockGroup $mockGroup): bool
    {
        return MockGroup::where('subject_id', $mockGroup->subject_id)
            ->where('exam_type_id', $mockGroup->exam_type_id)
            ->where('batch_number', '>', $mockGroup->batch_number)
            ->exists();
    }

    /**
     * Get next mock group
     */
    public function getNextGroup(MockGroup $mockGroup): ?MockGroup
    {
        return MockGroup::where('subject_id', $mockGroup->subject_id)
            ->where('exam_type_id', $mockGroup->exam_type_id)
            ->where('batch_number', $mockGroup->batch_number + 1)
            ->first();
    }

    /**
     * Get the first mock group for a subject and exam type
     */
    public function getFirstGroup(Subject $subject, ExamType $examType): ?MockGroup
    {
        return MockGroup::where('subject_id', $subject->id)
            ->where('exam_type_id', $examType->id)
            ->where('batch_number', 1)
            ->first();
    }
}
