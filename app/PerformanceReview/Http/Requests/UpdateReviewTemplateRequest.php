<?php

namespace App\PerformanceReview\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateReviewTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
        ];
    }
}
