<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class QuizStartRequest extends FormRequest
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
            'question_count' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'shuffle' => ['sometimes', 'boolean'],
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
            'question_count.integer' => 'Question count must be an integer.',
            'question_count.min' => 'Question count must be at least 1.',
            'question_count.max' => 'Question count cannot exceed 100.',
            'shuffle.boolean' => 'Shuffle must be a boolean.',
        ];
    }
}
