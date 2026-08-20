<?php

namespace App\PerformanceReview\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class StoreReviewAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * JSON requests (which is what Inertia's form.post() sends) bypass the
     * ConvertEmptyStringsToNull middleware, which only normalizes traditional
     * form-encoded data. Without this, an empty-string reviewer_id (left as ''
     * when the frontend doesn't set it for 'self' assignments) would still be
     * "present" as far as the `prohibited` rule is concerned, failing validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->input('reviewer_id') === '') {
            $this->merge(['reviewer_id' => null]);
        }
    }

    public function rules(): array
    {
        $isSelf = $this->input('review_type') === 'self';

        $revieweeRules = ['integer', 'distinct', 'exists:users,id'];
        if (! $isSelf) {
            $revieweeRules[] = 'different:reviewer_id';
        }

        return [
            'review_type' => ['required', 'string', 'in:self,peer,upward,downward'],
            'reviewer_id' => $isSelf
                ? ['prohibited']
                : ['required', 'integer', 'exists:users,id'],
            'reviewee_ids' => ['required', 'array', 'min:1'],
            'reviewee_ids.*' => $revieweeRules,
        ];
    }

    /**
     * Upward/downward assignments must follow the org chart: upward means
     * non-head staff reviewing their own department's Head of Department;
     * downward means a Head of Department reviewing their own staff.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $type = $this->input('review_type');

            if (! in_array($type, ['upward', 'downward'], true)) {
                return;
            }

            $reviewerId = $this->input('reviewer_id');
            $reviewer = $reviewerId ? User::with('department')->find($reviewerId) : null;

            if (! $reviewer || ! $reviewer->department) {
                $validator->errors()->add('reviewer_id', 'The selected reviewer has no department.');

                return;
            }

            $isHead = $reviewer->department->head_of_department_id === $reviewer->id;

            if ($type === 'upward' && $isHead) {
                $validator->errors()->add('reviewer_id', 'An upward reviewer must be staff, not the Head of Department.');
            }

            if ($type === 'downward' && ! $isHead) {
                $validator->errors()->add('reviewer_id', 'A downward reviewer must be the Head of Department.');
            }

            $revieweeIds = collect($this->input('reviewee_ids', []));

            if ($type === 'upward') {
                $headId = $reviewer->department->head_of_department_id;

                if ($revieweeIds->count() !== 1 || $revieweeIds->first() != $headId) {
                    $validator->errors()->add('reviewee_ids', 'An upward review must target only the reviewer\'s Head of Department.');
                }
            }

            if ($type === 'downward') {
                $departmentStaffIds = User::where('department_id', $reviewer->department_id)
                    ->where('id', '!=', $reviewer->id)
                    ->pluck('id');

                if ($revieweeIds->diff($departmentStaffIds)->isNotEmpty()) {
                    $validator->errors()->add('reviewee_ids', 'A downward review may only target staff in the reviewer\'s own department.');
                }
            }
        });
    }
}
