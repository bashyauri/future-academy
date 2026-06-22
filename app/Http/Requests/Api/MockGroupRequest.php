<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class MockGroupRequest extends FormRequest
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
            'subject_id' => ['required', 'exists:subjects,id'],
            'exam_type_id' => ['required', 'exists:exam_types,id'],
            'batch_number' => ['sometimes', 'integer', 'min:1'],
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
            'subject_id.required' => 'The subject ID is required.',
            'subject_id.exists' => 'The selected subject does not exist.',
            'exam_type_id.required' => 'The exam type ID is required.',
            'exam_type_id.exists' => 'The selected exam type does not exist.',
            'batch_number.integer' => 'The batch number must be an integer.',
            'batch_number.min' => 'The batch number must be at least 1.',
        ];
    }
}
