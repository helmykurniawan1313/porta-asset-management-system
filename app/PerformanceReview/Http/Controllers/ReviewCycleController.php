<?php

namespace App\PerformanceReview\Http\Controllers;

use App\Http\Controllers\Controller;
use App\PerformanceReview\Http\Requests\StoreReviewCycleRequest;
use App\PerformanceReview\Http\Requests\UpdateReviewCycleRequest;
use App\PerformanceReview\Models\ReviewCycle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ReviewCycleController extends Controller
{
    public function index(): View
    {
        return view('performance-review.review-cycles.index', [
            'reviewCycles' => ReviewCycle::orderByDesc('starts_at')->paginate(20),
        ]);
    }

    public function store(StoreReviewCycleRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['status'] = $data['status'] ?? 'draft';

        ReviewCycle::create($data);

        return Redirect::back()->with('status', 'review-cycle-created');
    }

    public function update(UpdateReviewCycleRequest $request, ReviewCycle $reviewCycle): RedirectResponse
    {
        $reviewCycle->update($request->validated());

        return Redirect::back()->with('status', 'review-cycle-updated');
    }

    public function destroy(ReviewCycle $reviewCycle): RedirectResponse
    {
        $reviewCycle->delete();

        return Redirect::back()->with('status', 'review-cycle-deleted');
    }
}
