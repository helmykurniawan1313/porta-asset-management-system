<?php

namespace App\PerformanceReview\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReviewCycleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'status' => ['nullable', 'string', 'in:draft,open,closed'],
        ];
    }
}
