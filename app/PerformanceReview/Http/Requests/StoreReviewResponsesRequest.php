<?php

namespace App\PerformanceReview\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReviewResponsesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'responses' => ['required', 'array', 'min:1'],
            'responses.*.question_id' => ['required', 'integer', 'exists:questions,id'],
            'responses.*.answer_rating' => ['nullable', 'integer', 'min:1', 'max:6'],
            'responses.*.answer_text' => ['nullable', 'string'],
        ];
    }
}
