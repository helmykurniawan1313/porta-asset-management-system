<?php

namespace App\PerformanceReview\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\User;
use App\PerformanceReview\Http\Requests\StoreReviewAssignmentRequest;
use App\PerformanceReview\Models\ReviewAssignment;
use App\PerformanceReview\Models\ReviewCycle;
use App\PerformanceReview\Models\ReviewResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ReviewAssignmentController extends Controller
{
    public function index(ReviewCycle $reviewCycle): View
    {
        return view('performance-review.review-assignments.index', [
            'reviewCycle' => $reviewCycle,
            'assignments' => $reviewCycle->reviewAssignments()
                ->with(['reviewer:id,name,email', 'reviewee:id,name,email'])
                ->orderBy('review_type')
                ->get(),
            'employees' => User::with('department:id,name,head_of_department_id')
                ->orderBy('name')
                ->get(['id', 'name', 'email', 'department_id']),
        ]);
    }

    /**
     * Read-only view of a single assignment's answers, for HR Admin.
     * Available regardless of status so HR can check progress on
     * in-progress drafts as well as fully submitted reviews.
     */
    public function show(ReviewCycle $reviewCycle, ReviewAssignment $assignment): View
    {
        $template = $reviewCycle->reviewTemplates()
            ->with(['questions' => fn ($q) => $q->orderBy('order')])
            ->first();

        $responses = ReviewResponse::where('review_assignment_id', $assignment->id)
            ->get()
            ->keyBy('question_id');

        return view('performance-review.review-assignments.show', [
            'reviewCycle' => $reviewCycle,
            'assignment' => $assignment->load(['reviewer:id,name,email', 'reviewee:id,name,email']),
            'template' => $template,
            'responses' => $responses->values(),
        ]);
    }

    /**
     * Bulk-create assignments for the given review type.
     * For 'self' reviews, reviewer_id == reviewee_id for each selected employee
     * (reviewer_id is not accepted from the request in that case).
     * For peer/upward/downward reviews, a single explicit reviewer is paired
     * against each selected reviewee.
     * Duplicate (cycle, reviewer, reviewee, type) combinations are silently skipped.
     */
    public function store(StoreReviewAssignmentRequest $request, ReviewCycle $reviewCycle): RedirectResponse
    {
        $data = $request->validated();
        $isSelf = $data['review_type'] === 'self';

        $created = 0;
        $skipped = 0;

        foreach ($data['reviewee_ids'] as $revieweeId) {
            $reviewerId = $isSelf ? $revieweeId : $data['reviewer_id'];

            $exists = ReviewAssignment::where([
                'review_cycle_id' => $reviewCycle->id,
                'reviewer_id' => $reviewerId,
                'reviewee_id' => $revieweeId,
                'review_type' => $data['review_type'],
            ])->exists();

            if ($exists) {
                $skipped++;

                continue;
            }

            ReviewAssignment::create([
                'review_cycle_id' => $reviewCycle->id,
                'reviewer_id' => $reviewerId,
                'reviewee_id' => $revieweeId,
                'review_type' => $data['review_type'],
                'status' => 'pending',
            ]);

            $created++;
        }

        return Redirect::back()->with([
            'status' => 'assignments-created',
            'assignmentResults' => ['created' => $created, 'skipped' => $skipped],
        ]);
    }

    public function destroy(ReviewCycle $reviewCycle, ReviewAssignment $assignment): RedirectResponse
    {
        $assignment->delete();

        return Redirect::back()->with('status', 'assignment-deleted');
    }

    /**
     * One-click bulk creation of every valid upward or downward assignment,
     * derived directly from the org chart — no manual reviewer/reviewee
     * picking needed. Upward: every staff member reviews their own
     * department's Head of Department. Downward: every Head of Department
     * reviews every staff member in their own department. Only top-level
     * departments have a Head — a department's staff pool for this purpose
     * includes its own users plus every sub-department's users (e.g.
     * Operational's head reviews/is-reviewed-by Finance, IT, HR, etc., not
     * just people tagged directly as "Operational").
     */
    public function bulkOrgChart(Request $request, ReviewCycle $reviewCycle): RedirectResponse
    {
        $type = $request->string('review_type')->toString();

        if (! in_array($type, ['upward', 'downward'], true)) {
            return Redirect::back()->withErrors(['review_type' => 'Bulk creation only supports upward or downward review types.']);
        }

        $departments = Department::whereNotNull('head_of_department_id')->get();

        $created = 0;
        $skipped = 0;

        foreach ($departments as $department) {
            $headId = $department->head_of_department_id;
            $staff = $department->allUsers()->where('id', '!=', $headId)->get();

            foreach ($staff as $staffMember) {
                [$reviewerId, $revieweeId] = $type === 'upward'
                    ? [$staffMember->id, $headId]
                    : [$headId, $staffMember->id];

                $exists = ReviewAssignment::where([
                    'review_cycle_id' => $reviewCycle->id,
                    'reviewer_id' => $reviewerId,
                    'reviewee_id' => $revieweeId,
                    'review_type' => $type,
                ])->exists();

                if ($exists) {
                    $skipped++;

                    continue;
                }

                ReviewAssignment::create([
                    'review_cycle_id' => $reviewCycle->id,
                    'reviewer_id' => $reviewerId,
                    'reviewee_id' => $revieweeId,
                    'review_type' => $type,
                    'status' => 'pending',
                ]);

                $created++;
            }
        }

        return Redirect::back()->with([
            'status' => 'assignments-created',
            'assignmentResults' => ['created' => $created, 'skipped' => $skipped],
        ]);
    }
}
