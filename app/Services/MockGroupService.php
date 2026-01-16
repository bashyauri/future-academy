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
     * Group mock questions for a subject and exam type into batches
     */
    public function groupMockQuestions(
        Subject $subject,
        ExamType $examType,
        int $batchSize = self::DEFAULT_BATCH_SIZE
    ): void {
        // Get all mock questions for this subject and exam type, ordered by ID
        $mockQuestions = Question::where('subject_id', $subject->id)
            ->where('exam_type_id', $examType->id)
            ->where('is_mock', true)
            ->orderBy('id')
            ->get();

        if ($mockQuestions->isEmpty()) {
            return;
        }

        // Clear existing groups for this subject-exam combo
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
