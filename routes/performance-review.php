<?php

use App\PerformanceReview\Http\Controllers\EmployeeReviewController;
use App\PerformanceReview\Http\Controllers\EmployeeReviewReportController;
use App\PerformanceReview\Http\Controllers\MyReviewController;
use App\PerformanceReview\Http\Controllers\PasswordSetupController;
use App\PerformanceReview\Http\Controllers\QuestionController;
use App\PerformanceReview\Http\Controllers\ReviewAssignmentController;
use App\PerformanceReview\Http\Controllers\ReviewCycleController;
use App\PerformanceReview\Http\Controllers\ReviewTemplateController;
use Illuminate\Support\Facades\Route;

// Note: Department and Employee management live under the existing
// departments.* / employees.* routes (see routes/web.php) — it-inventory
// already had its own Departments/Employees CRUD, so the ported
// review-system controllers for those were merged into the existing ones
// rather than duplicated here.
Route::middleware(['auth', 'role:HR Admin|Super Admin'])
    ->prefix('hr')
    ->name('hr.')
    ->group(function () {
        // Review Cycles
        Route::get('/review-cycles', [ReviewCycleController::class, 'index'])->name('review-cycles.index');
        Route::post('/review-cycles', [ReviewCycleController::class, 'store'])->name('review-cycles.store');
        Route::patch('/review-cycles/{reviewCycle}', [ReviewCycleController::class, 'update'])->name('review-cycles.update');
        Route::delete('/review-cycles/{reviewCycle}', [ReviewCycleController::class, 'destroy'])->name('review-cycles.destroy');

        // Review Templates (nested under review cycle)
        Route::get('/review-cycles/{reviewCycle}/templates', [ReviewTemplateController::class, 'index'])->name('review-cycles.templates.index');
        Route::post('/review-cycles/{reviewCycle}/templates', [ReviewTemplateController::class, 'store'])->name('review-cycles.templates.store');
        Route::patch('/review-cycles/{reviewCycle}/templates/{template}', [ReviewTemplateController::class, 'update'])->name('review-cycles.templates.update');
        Route::delete('/review-cycles/{reviewCycle}/templates/{template}', [ReviewTemplateController::class, 'destroy'])->name('review-cycles.templates.destroy');

        // Questions (nested under review template)
        Route::post('/review-templates/{reviewTemplate}/questions', [QuestionController::class, 'store'])->name('review-templates.questions.store');
        Route::patch('/review-templates/{reviewTemplate}/questions/{question}', [QuestionController::class, 'update'])->name('review-templates.questions.update');
        Route::delete('/review-templates/{reviewTemplate}/questions/{question}', [QuestionController::class, 'destroy'])->name('review-templates.questions.destroy');
        Route::post('/review-templates/{reviewTemplate}/questions/reorder', [QuestionController::class, 'reorder'])->name('review-templates.questions.reorder');

        // Review Assignments (nested under review cycle)
        Route::get('/review-cycles/{reviewCycle}/assignments', [ReviewAssignmentController::class, 'index'])->name('review-cycles.assignments.index');
        Route::post('/review-cycles/{reviewCycle}/assignments', [ReviewAssignmentController::class, 'store'])->name('review-cycles.assignments.store');
        Route::post('/review-cycles/{reviewCycle}/assignments/bulk-org-chart', [ReviewAssignmentController::class, 'bulkOrgChart'])->name('review-cycles.assignments.bulk-org-chart');
        Route::get('/review-cycles/{reviewCycle}/assignments/{assignment}', [ReviewAssignmentController::class, 'show'])->name('review-cycles.assignments.show');
        Route::delete('/review-cycles/{reviewCycle}/assignments/{assignment}', [ReviewAssignmentController::class, 'destroy'])->name('review-cycles.assignments.destroy');

        // Employee Reviews — per-employee summary of all reviews received, across every cycle.
        Route::get('/employee-reviews', [EmployeeReviewController::class, 'index'])->name('employee-reviews.index');
        Route::get('/employee-reviews/export', [EmployeeReviewController::class, 'downloadAll'])->name('employee-reviews.download-all');
        Route::get('/employee-reviews/{employee}', [EmployeeReviewController::class, 'show'])->name('employee-reviews.show');
        Route::get('/employee-reviews/{employee}/report', [EmployeeReviewReportController::class, 'show'])->name('employee-reviews.report');
        Route::get('/employee-reviews/{employee}/report/download', [EmployeeReviewReportController::class, 'download'])->name('employee-reviews.report.download');
    });

// Signed password-setup links emailed to newly created users (not authenticated yet).
Route::middleware(['guest', 'signed'])->group(function () {
    Route::get('/password-setup/{user}', [PasswordSetupController::class, 'show'])->name('password.setup');
    Route::post('/password-setup/{user}', [PasswordSetupController::class, 'store'])->name('password.setup.store');
});

// My Reviews — any authenticated user viewing/filling out review assignments where they are the reviewer.
Route::middleware(['auth'])->group(function () {
    Route::get('/my-reviews', [MyReviewController::class, 'index'])->name('my-reviews.index');
    Route::get('/my-reviews/report', [MyReviewController::class, 'report'])->name('my-reviews.report');
    Route::get('/my-reviews/{assignment}', [MyReviewController::class, 'show'])->name('my-reviews.show');
    Route::post('/my-reviews/{assignment}', [MyReviewController::class, 'store'])->name('my-reviews.store');
});
