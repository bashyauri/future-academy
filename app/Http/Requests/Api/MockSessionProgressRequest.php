<?php

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class MockSessionProgressRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'question_id' => ['sometimes', 'required_with:option_id', 'integer'],
            'option_id' => ['sometimes', 'nullable', 'integer'],
            'answers' => ['sometimes', 'array'],
            'answers.*' => ['nullable', 'integer'],
            'current_question_index' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
