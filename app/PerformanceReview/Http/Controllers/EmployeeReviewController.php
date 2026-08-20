<?php

namespace App\PerformanceReview\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\PerformanceReview\Exports\AllEmployeeReviewsExport;
use App\PerformanceReview\Models\ReviewAssignment;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class EmployeeReviewController extends Controller
{
    /**
     * Per-employee summary of every review assignment where they are the
     * reviewee, across all review cycles — counts by status plus an average
     * rating from submitted rating_scale answers.
     */
    public function index(): View
    {
        $employees = User::with('department:id,name')
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'department_id']);

        $assignments = ReviewAssignment::query()
            ->select(['id', 'reviewee_id', 'review_type', 'status'])
            ->withCount([
                'reviewResponses as rating_count' => fn ($q) => $q->whereNotNull('answer_rating'),
            ])
            ->withAvg([
                'reviewResponses as average_rating' => fn ($q) => $q->whereNotNull('answer_rating'),
            ], 'answer_rating')
            ->get()
            ->groupBy('reviewee_id');

        $summaries = $employees->map(function (User $employee) use ($assignments) {
            $rows = $assignments->get($employee->id, collect());

            $ratedRows = $rows->filter(fn ($row) => $row->rating_count > 0);
            $averageRating = $ratedRows->isNotEmpty()
                ? round($ratedRows->avg('average_rating'), 2)
                : null;

            return [
                'id' => $employee->id,
                'name' => $employee->name,
                'email' => $employee->email,
                'department' => $employee->department?->name,
                'total' => $rows->count(),
                'pending' => $rows->where('status', 'pending')->count(),
                'in_progress' => $rows->where('status', 'in_progress')->count(),
                'submitted' => $rows->where('status', 'submitted')->count(),
                'average_rating' => $averageRating,
            ];
        });

        return view('performance-review.employee-reviews.index', [
            'summaries' => $summaries->values(),
        ]);
    }

    /**
     * All review assignments received by a single employee, across every
     * cycle and review type — the drill-down behind the summary row.
     */
    public function show(User $employee): View
    {
        $assignments = ReviewAssignment::where('reviewee_id', $employee->id)
            ->with(['reviewer:id,name,email', 'reviewCycle:id,name'])
            ->orderByDesc('id')
            ->get();

        return view('performance-review.employee-reviews.show', [
            'employee' => $employee,
            'assignments' => $assignments,
        ]);
    }

    /**
     * One workbook, one sheet per employee, each holding that employee's
     * full review report (all types, rating matrices + text answers).
     */
    public function downloadAll(AllEmployeeReviewsExport $export): BinaryFileResponse
    {
        // One sheet per employee across the whole org is a large workbook;
        // give it more headroom than the default request memory limit.
        ini_set('memory_limit', '1024M');

        return Excel::download($export, 'all-employee-reviews.xlsx');
    }
}
