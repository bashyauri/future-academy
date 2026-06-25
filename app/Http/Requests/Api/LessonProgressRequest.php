<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class LessonProgressRequest extends FormRequest
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
            'current_time_seconds' => ['sometimes', 'integer', 'min:0'],
            'progress_percentage' => ['sometimes', 'integer', 'min:0', 'max:100'],
            'time_spent_seconds' => ['sometimes', 'integer', 'min:0'],
            'is_completed' => ['sometimes', 'boolean'],
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
            'current_time_seconds.integer' => 'Current time must be an integer.',
            'current_time_seconds.min' => 'Current time must be at least 0.',
            'progress_percentage.integer' => 'Progress percentage must be an integer.',
            'progress_percentage.min' => 'Progress percentage must be at least 0.',
            'progress_percentage.max' => 'Progress percentage cannot exceed 100.',
            'time_spent_seconds.integer' => 'Time spent must be an integer.',
            'time_spent_seconds.min' => 'Time spent must be at least 0.',
            'is_completed.boolean' => 'Completion status must be a boolean.',
        ];
    }
}
