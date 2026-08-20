<?php

namespace App\PerformanceReview\Http\Controllers;

use App\Http\Controllers\Controller;
use App\PerformanceReview\Http\Requests\StoreReviewResponsesRequest;
use App\PerformanceReview\Models\ReviewAssignment;
use App\PerformanceReview\Models\ReviewResponse;
use App\PerformanceReview\Services\EmployeeReviewReportBuilder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class MyReviewController extends Controller
{
    public function __construct(private readonly EmployeeReviewReportBuilder $reportBuilder)
    {
    }

    public function index(): View
    {
        $assignments = ReviewAssignment::where('reviewer_id', Auth::id())
            ->with(['reviewee:id,name,email', 'reviewCycle:id,name,status'])
            ->orderByDesc('created_at')
            ->get();

        return view('performance-review.my-reviews.index', [
            'assignments' => $assignments,
        ]);
    }

    public function show(ReviewAssignment $assignment): View
    {
        $this->authorizeOwnAssignment($assignment);

        $template = $assignment->reviewCycle
            ->reviewTemplates()
            ->with(['questions' => fn ($q) => $q->orderBy('order')])
            ->first();

        $existingResponses = ReviewResponse::where('review_assignment_id', $assignment->id)
            ->get()
            ->keyBy('question_id');

        return view('performance-review.my-reviews.show', [
            'assignment' => $assignment->load('reviewee:id,name,email', 'reviewCycle:id,name,status'),
            'template' => $template,
            'existingResponses' => $existingResponses->values(),
        ]);
    }

    public function store(StoreReviewResponsesRequest $request, ReviewAssignment $assignment): RedirectResponse
    {
        $this->authorizeOwnAssignment($assignment);

        if ($assignment->status === 'submitted') {
            return Redirect::back()->withErrors(['assignment' => 'This review has already been submitted and cannot be edited.']);
        }

        $data = $request->validated();
        $submit = $request->boolean('submit');

        DB::transaction(function () use ($data, $assignment, $submit) {
            foreach ($data['responses'] as $response) {
                ReviewResponse::updateOrCreate(
                    [
                        'review_assignment_id' => $assignment->id,
                        'question_id' => $response['question_id'],
                    ],
                    [
                        'answer_rating' => $response['answer_rating'] ?? null,
                        'answer_text' => $response['answer_text'] ?? null,
                    ]
                );
            }

            $assignment->update([
                'status' => $submit ? 'submitted' : 'in_progress',
                'submitted_at' => $submit ? now() : $assignment->submitted_at,
            ]);
        });

        return Redirect::route('my-reviews.index')->with('status', $submit ? 'review-submitted' : 'review-saved');
    }

    /**
     * Self-service consolidated report of every review the current user has
     * received (self/peer/upward/downward). Reviewer identities are always
     * anonymized here — unlike the HR-facing report, which shows real names
     * — since a Head of Department seeing their own upward feedback (or any
     * employee seeing peer feedback) should not be able to identify who
     * wrote what.
     */
    public function report(): View
    {
        $user = Auth::user();

        return view('performance-review.my-reviews.report', [
            'employee' => $user,
            'sections' => $this->reportBuilder->build($user, ['peer', 'upward', 'downward']),
        ]);
    }

    protected function authorizeOwnAssignment(ReviewAssignment $assignment): void
    {
        if ($assignment->reviewer_id !== Auth::id()) {
            throw new NotFoundHttpException;
        }
    }
}
