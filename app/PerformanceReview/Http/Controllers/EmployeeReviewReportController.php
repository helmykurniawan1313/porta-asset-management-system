<?php

namespace App\PerformanceReview\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\PerformanceReview\Services\EmployeeReviewReportBuilder;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\View\View;

class EmployeeReviewReportController extends Controller
{
    public function __construct(private readonly EmployeeReviewReportBuilder $reportBuilder)
    {
    }

    /**
     * Consolidated report for one employee: for every review type they've
     * received at least one submitted review for, a rating matrix (question
     * rows x reviewer columns, with a row average) plus the free-text answers
     * from each reviewer.
     */
    public function show(User $employee): View
    {
        return view('performance-review.employee-reviews.report', [
            'employee' => $employee->only(['id', 'name', 'email']),
            'sections' => $this->reportBuilder->build($employee),
        ]);
    }

    /**
     * Same report content, rendered server-side to a downloadable PDF via a
     * dedicated dense Blade layout (smaller type, tighter rows) so a single
     * page fits far more than the on-screen version.
     */
    public function download(User $employee): HttpResponse
    {
        $sections = $this->reportBuilder->build($employee);

        $pdf = Pdf::loadView('reports.employee-review', [
            'employee' => $employee,
            'sections' => $sections,
        ])->setPaper('a4', 'portrait');

        $filename = str($employee->name)->slug('-')."-review-report.pdf";

        return $pdf->download($filename);
    }
}
