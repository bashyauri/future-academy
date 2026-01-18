<?php

namespace App\Services;

use App\Models\MockGroup;
use App\Models\Question;
use App\Models\Subject;
use App\Models\ExamType;

class MockGroupService
{
    const DEFAULT_BATCH_SIZE = 40;

    /**
     * Get batch size from config based on exam type and subject
     */
    protected function getBatchSizeFromConfig(ExamType $examType, Subject $subject): int
    {
        $examTypeFormat = strtolower($examType->exam_format ?? 'default');
        $subjectName = strtolower($subject->name);

        // Get the format config for this exam type
        $formats = config('mock.formats', []);
        $formatConfig = $formats[$examTypeFormat] ?? $formats['default'] ?? null;

        if (!$formatConfig) {
            return self::DEFAULT_BATCH_SIZE;
        }

        // Check per_subject rules
        if (isset($formatConfig['per_subject'])) {
            foreach ($formatConfig['per_subject'] as $rule) {
                $matches = $rule['match'] ?? [];
                foreach ($matches as $pattern) {
                    if (str_contains($subjectName, strtolower($pattern))) {
                        return $rule['questions'] ?? self::DEFAULT_BATCH_SIZE;
                    }
                }
            }
        }

        // Fall back to default for this exam type
        return $formatConfig['default']['questions'] ?? self::DEFAULT_BATCH_SIZE;
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
        $batchSize = $batchSize ?? $this->getBatchSizeFromConfig($examType, $subject);
        // Get all mock questions for this subject and exam type, ordered by ID
        $mockQuestions = Question::where('subject_id', $subject->id)
            ->where('exam_type_id', $examType->id)
            ->where('is_mock', true)
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
