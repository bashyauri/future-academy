<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\ValidationException;
use Illuminate\Http\JsonResponse;

class SyncRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'attempts' => 'sometimes|array',
            'attempts.*.uuid' => 'required|string|max:255',
            'attempts.*.user_id' => 'required|integer|exists:users,id',
            'attempts.*.quiz_id' => 'nullable|integer|exists:quizzes,id',
            'attempts.*.exam_type_id' => 'nullable|integer',
            'attempts.*.subject_id' => 'nullable|integer|exists:subjects,id',
            'attempts.*.mock_group_id' => 'nullable|integer',
            'attempts.*.exam_year' => 'nullable|integer|min:2000|max:2100',
            'attempts.*.attempt_number' => 'nullable|integer|min:1',
            'attempts.*.started_at' => 'required|date',
            'attempts.*.completed_at' => 'nullable|date|after:started_at',
            'attempts.*.time_taken_seconds' => 'nullable|integer|min:0',
            'attempts.*.total_questions' => 'required|integer|min:0',
            'attempts.*.answered_questions' => 'nullable|integer|min:0|max:attempts.*.total_questions',
            'attempts.*.correct_answers' => 'required|integer|min:0|max:attempts.*.total_questions',
            'attempts.*.score' => 'nullable|numeric|min:0',
            'attempts.*.percentage' => 'nullable|numeric|min:0|max:100',
            'attempts.*.score_percentage' => 'nullable|numeric|min:0|max:100',
            'attempts.*.passed' => 'nullable|boolean',
            'attempts.*.status' => 'required|in:in_progress,completed',
            'attempts.*.question_order' => 'nullable|array',
            'attempts.*.current_question_index' => 'nullable|integer|min:0',

            'answers' => 'sometimes|array|max:1000', // Max 1000 answers per sync
            'answers.*.attempt_uuid' => 'required|string|max:255',
            'answers.*.question_id' => 'required|integer|exists:questions,id',
            'answers.*.option_id' => 'nullable|integer|exists:options,id',
            'answers.*.is_correct' => 'required|boolean',
            'answers.*.time_spent_seconds' => 'nullable|integer|min:0',

            'lesson_progress' => 'sometimes|array|max:100', // Max 100 lesson progress updates per sync
            'lesson_progress.*.user_id' => 'required|integer|exists:users,id',
            'lesson_progress.*.lesson_id' => 'required|integer|exists:lessons,id',
            'lesson_progress.*.current_time_seconds' => 'nullable|integer|min:0',
            'lesson_progress.*.progress_percentage' => 'nullable|integer|min:0|max:100',
            'lesson_progress.*.is_completed' => 'nullable|boolean',
            'lesson_progress.*.time_spent_seconds' => 'nullable|integer|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'attempts.*.uuid.required' => 'Attempt UUID is required.',
            'attempts.*.user_id.required' => 'User ID is required.',
            'attempts.*.user_id.exists' => 'User does not exist.',
            'attempts.*.quiz_id.exists' => 'Quiz does not exist.',
            'attempts.*.subject_id.exists' => 'Subject does not exist.',
            'attempts.*.started_at.required' => 'Start time is required.',
            'attempts.*.completed_at.after' => 'Completion time must be after start time.',
            'attempts.*.total_questions.required' => 'Total questions count is required.',
            'attempts.*.correct_answers.required' => 'Correct answers count is required.',
            'attempts.*.correct_answers.max' => 'Correct answers cannot exceed total questions.',
            'attempts.*.status.in' => 'Status must be either in_progress or completed.',
            
            'answers.*.attempt_uuid.required' => 'Attempt UUID is required.',
            'answers.*.question_id.required' => 'Question ID is required.',
            'answers.*.question_id.exists' => 'Question does not exist.',
            'answers.*.option_id.exists' => 'Option does not exist.',
            'answers.*.is_correct.required' => 'is_correct field is required.',
            
            'lesson_progress.*.user_id.required' => 'User ID is required.',
            'lesson_progress.*.user_id.exists' => 'User does not exist.',
            'lesson_progress.*.lesson_id.required' => 'Lesson ID is required.',
            'lesson_progress.*.lesson_id.exists' => 'Lesson does not exist.',
            'lesson_progress.*.progress_percentage.max' => 'Progress percentage cannot exceed 100.',
        ];
    }

    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        $response = new JsonResponse([
            'message' => 'Validation failed',
            'errors' => $validator->errors(),
        ], 422);

        throw new ValidationException($validator, $response);
    }
}
