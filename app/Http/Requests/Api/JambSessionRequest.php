<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class JambSessionRequest extends FormRequest
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
            'subject_ids' => ['required', 'array', 'size:4'],
            'subject_ids.*' => ['required', 'integer', 'exists:subjects,id'],
            'year' => ['nullable', 'integer'],
            'questions_per_subject' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:200'],
            'time_limit' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:300'],
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
            'subject_ids.required' => 'Please select subjects.',
            'subject_ids.array' => 'Subjects must be an array.',
            'subject_ids.size' => 'You must select exactly 4 subjects for JAMB.',
            'subject_ids.*.exists' => 'One or more selected subjects are invalid.',
            'questions_per_subject.integer' => 'Questions per subject must be an integer.',
            'questions_per_subject.min' => 'Questions per subject must be at least 1.',
            'questions_per_subject.max' => 'Questions per subject cannot exceed 200.',
            'time_limit.integer' => 'Time limit must be an integer.',
            'time_limit.min' => 'Time limit must be at least 1 minute.',
            'time_limit.max' => 'Time limit cannot exceed 300 minutes.',
            'shuffle.boolean' => 'Shuffle must be a boolean.',
        ];
    }
}
