<?php

namespace App\Http\Requests\Api;

use App\Models\ExamType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class MockSessionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'subject_ids' => ['required', 'array', 'min:1'],
            'subject_ids.*' => ['required', 'integer', 'distinct', 'exists:subjects,id'],
            'exam_type_id' => ['required', 'exists:exam_types,id'],
            'duration_minutes' => ['sometimes', 'integer', 'min:30', 'max:180'],
            'mock_group_id' => ['sometimes', 'integer', 'exists:mock_groups,id'],
        ];

        $examType = ExamType::query()->find($this->input('exam_type_id'));
        if (strtolower((string) $examType?->exam_format) === 'jamb') {
            $rules['subject_ids'][] = 'max:4';
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'subject_ids.required' => 'At least one subject ID is required.',
            'subject_ids.array' => 'Subject IDs must be an array.',
            'subject_ids.min' => 'At least one subject must be selected.',
            'subject_ids.max' => 'JAMB mock exams support a maximum of 4 subjects.',
            'subject_ids.*.required' => 'Each subject ID is required.',
            'subject_ids.*.exists' => 'One or more selected subjects do not exist.',
            'exam_type_id.required' => 'The exam type ID is required.',
            'exam_type_id.exists' => 'The selected exam type does not exist.',
            'duration_minutes.integer' => 'Duration must be an integer.',
            'duration_minutes.min' => 'Duration must be at least 30 minutes.',
            'duration_minutes.max' => 'Duration cannot exceed 180 minutes.',
        ];
    }
}
