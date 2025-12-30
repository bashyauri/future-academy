<?php

namespace App\Filament\Resources\Quizzes\Pages;

use App\Enums\QuizType;
use App\Filament\Resources\Quizzes\QuizResource;
use App\Models\ExamType;
use App\Models\Subject;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditQuiz extends EditRecord
{
    protected static string $resource = QuizResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Handle mock exam specific settings
        if (($data['type'] ?? null) === QuizType::Mock->value) {
            // Set duration to 100 minutes for JAMB mock exams
            $data['duration_minutes'] = 100;

            // Disable shuffling and answer display for mock exams
            $data['randomize_questions'] = false;
            $data['shuffle_questions'] = false;
            $data['shuffle_options'] = false;
            $data['show_answers_after_submit'] = false;
            $data['show_explanations'] = false;

            // Calculate question count based on subjects
            // JAMB English = 70 questions, all others = 50
            $totalQuestions = 0;

            if (!empty($data['subject_ids']) && is_array($data['subject_ids'])) {
                // Check if this is a JAMB exam
                $examTypeIds = $data['exam_type_ids'] ?? [];
                $isJamb = false;

                if (!empty($examTypeIds)) {
                    $jambExamType = ExamType::whereIn('id', $examTypeIds)
                        ->where('name', 'LIKE', '%JAMB%')
                        ->first();
                    $isJamb = $jambExamType !== null;
                }

                if ($isJamb) {
                    foreach ($data['subject_ids'] as $subjectId) {
                        $subject = Subject::find($subjectId);

                        // Check if subject is English
                        if ($subject && stripos($subject->name, 'English') !== false) {
                            $totalQuestions += 70;
                        } else {
                            $totalQuestions += 50;
                        }
                    }
                } else {
                    // For non-JAMB mock exams, default to 50 questions per subject
                    $totalQuestions = count($data['subject_ids']) * 50;
                }
            } else {
                // Default to 50 if no subjects selected
                $totalQuestions = 50;
            }

            $data['question_count'] = $totalQuestions;
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $quiz = $this->record;
        $manualQuestions = collect($this->data['questions'] ?? [])
            ->filter(fn($q) => !empty($q['question_id']))
            ->mapWithKeys(fn($q, $i) => [
                $q['question_id'] => ['order' => $i + 1],
            ]);

        if ($manualQuestions->isNotEmpty()) {
            $quiz->questions()->sync($manualQuestions);
            return;
        }

        // Criteria-based selection
        $criteria = [
            'subject_ids' => $this->data['subject_ids'] ?? [],
            'topic_ids' => $this->data['topic_ids'] ?? [],
            'exam_type_ids' => $this->data['exam_type_ids'] ?? [],
            'difficulty_levels' => $this->data['difficulty_levels'] ?? [],
            'years' => $this->data['years'] ?? [],
        ];

        $query = \App\Models\Question::query()->approved()->active();
        if (!empty($criteria['subject_ids'])) {
            $query->whereIn('subject_id', $criteria['subject_ids']);
        }
        if (!empty($criteria['topic_ids'])) {
            $query->whereIn('topic_id', $criteria['topic_ids']);
        }
        if (!empty($criteria['exam_type_ids'])) {
            $query->whereIn('exam_type_id', $criteria['exam_type_ids']);
        }
        if (!empty($criteria['difficulty_levels'])) {
            $query->whereIn('difficulty', $criteria['difficulty_levels']);
        }
        if (!empty($criteria['years'])) {
            $query->whereIn('year', $criteria['years']);
        }

        $questionCount = $this->data['question_count'] ?? null;
        $questionCount = is_numeric($questionCount) ? (int)$questionCount : 0;
        if ($questionCount > 0) {
            $query->limit($questionCount);
        }

        $criteriaQuestions = $query->get();
        $syncData = $criteriaQuestions->mapWithKeys(function($q, $i) {
            $i = is_numeric($i) ? (int)$i : 0;
            return [
                $q->id => ['order' => $i + 1],
            ];
        });
        $quiz->questions()->sync($syncData);
    }
}
