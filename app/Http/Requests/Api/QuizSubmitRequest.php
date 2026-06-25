<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class QuizSubmitRequest extends FormRequest
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
        return [
            'answers' => ['required', 'array', 'min:1'],
            'answers.*.question_id' => ['required', 'integer', 'exists:questions,id'],
            'answers.*.option_id' => ['required', 'integer', 'exists:options,id'],
            'answers.*.time_spent_seconds' => ['sometimes', 'integer', 'min:0'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'answers.required' => 'Answers array is required.',
            'answers.array' => 'Answers must be an array.',
            'answers.min' => 'At least one answer is required.',
            'answers.*.question_id.required' => 'Question ID is required for each answer.',
            'answers.*.question_id.exists' => 'One or more questions do not exist.',
            'answers.*.option_id.required' => 'Option ID is required for each answer.',
            'answers.*.option_id.exists' => 'One or more options do not exist.',
            'answers.*.time_spent_seconds.integer' => 'Time spent must be an integer.',
            'answers.*.time_spent_seconds.min' => 'Time spent must be at least 0.',
        ];
    }
}
