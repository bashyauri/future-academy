<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class DownloadJambRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'subjects' => 'required|string',
            'year' => 'nullable|integer|min:2000|max:2100',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:10|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'subjects.required' => 'The subjects parameter is required.',
            'subjects.string' => 'The subjects must be a string.',
            'year.integer' => 'The year must be a valid integer.',
            'year.min' => 'The year must be 2000 or later.',
            'year.max' => 'The year must be 2100 or earlier.',
        ];
    }
}
