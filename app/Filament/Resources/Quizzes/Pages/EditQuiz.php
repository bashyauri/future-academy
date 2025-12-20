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
}
