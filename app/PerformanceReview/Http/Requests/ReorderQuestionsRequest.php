<?php

namespace App\PerformanceReview\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReorderQuestionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'questions' => ['required', 'array'],
            'questions.*.id' => ['required', 'integer', 'exists:questions,id'],
            'questions.*.order' => ['required', 'integer'],
        ];
    }
}
