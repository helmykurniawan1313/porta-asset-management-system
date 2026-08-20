<?php

namespace App\PerformanceReview\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'text' => ['required', 'string'],
            'type' => ['required', 'string', 'in:rating_scale,text,multiple_choice'],
            'options' => $this->input('type') === 'multiple_choice'
                ? ['required', 'array']
                : ['prohibited'],
            'order' => ['nullable', 'integer'],
        ];
    }
}
