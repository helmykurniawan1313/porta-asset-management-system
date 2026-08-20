<?php

namespace App\PerformanceReview\Http\Controllers;

use App\Http\Controllers\Controller;
use App\PerformanceReview\Http\Requests\StoreReviewTemplateRequest;
use App\PerformanceReview\Http\Requests\UpdateReviewTemplateRequest;
use App\PerformanceReview\Models\ReviewCycle;
use App\PerformanceReview\Models\ReviewTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ReviewTemplateController extends Controller
{
    public function index(ReviewCycle $reviewCycle): View
    {
        return view('performance-review.review-templates.index', [
            'reviewCycle' => $reviewCycle,
            'reviewTemplates' => $reviewCycle->reviewTemplates()->with('questions')->orderBy('name')->get(),
        ]);
    }

    public function store(StoreReviewTemplateRequest $request, ReviewCycle $reviewCycle): RedirectResponse
    {
        $data = $request->validated();
        $data['review_cycle_id'] = $reviewCycle->id;

        ReviewTemplate::create($data);

        return Redirect::back()->with('status', 'review-template-created');
    }

    public function update(UpdateReviewTemplateRequest $request, ReviewCycle $reviewCycle, ReviewTemplate $template): RedirectResponse
    {
        $template->update($request->validated());

        return Redirect::back()->with('status', 'review-template-updated');
    }

    public function destroy(ReviewCycle $reviewCycle, ReviewTemplate $template): RedirectResponse
    {
        $template->delete();

        return Redirect::back()->with('status', 'review-template-deleted');
    }
}
