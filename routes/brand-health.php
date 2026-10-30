<?php

use App\BrandHealth\Http\Controllers\BrandAccountController;
use App\BrandHealth\Http\Controllers\BrandCycleController;
use App\BrandHealth\Http\Controllers\BrandFormulaWeightController;
use App\BrandHealth\Http\Controllers\BrandLabelBucketController;
use App\BrandHealth\Http\Controllers\BrandLegalPageController;
use App\BrandHealth\Http\Controllers\BrandPerformanceController;
use App\BrandHealth\Http\Controllers\BrandScoreBucketController;
use Illuminate\Support\Facades\Route;

// Brand Health module (ported from Brand Matrix). Read routes are open to any
// authenticated user; write routes are gated behind 'edit-brand-health'
// (temporary: only test@example.com, see AppServiceProvider).
Route::middleware('auth')->prefix('brand-health')->name('brand-health.')->group(function () {

    // --- Read-only: any authenticated user ---

    Route::resource('accounts', BrandAccountController::class)->only(['index']);
    Route::get('accounts/{account}/growth', [BrandAccountController::class, 'growth'])->name('accounts.growth');
    Route::post('accounts/{account}/pdf', [BrandAccountController::class, 'pdf'])->name('accounts.pdf');
    Route::post('accounts/{account}/summarize', [BrandAccountController::class, 'summarize'])->name('accounts.summarize');

    Route::resource('cycles', BrandCycleController::class)->only(['index']);
    Route::get('accounts/{account}/neighboring-cycle', [BrandCycleController::class, 'neighboring'])->name('cycles.neighboring');
    Route::get('cycles-pdf', [BrandCycleController::class, 'pdf'])->name('cycles.pdf');
    Route::get('cycles/{cycle}/pdf', [BrandCycleController::class, 'pdfSingle'])->name('cycles.pdf-single');
    Route::post('cycles/{cycle}/summarize', [BrandCycleController::class, 'summarize'])->name('cycles.summarize');
    Route::post('cycles-summarize', [BrandCycleController::class, 'summarizeFiltered'])->name('cycles.summarize-filtered');

    Route::get('scoring', [BrandScoreBucketController::class, 'index'])->name('scoring.index');

    Route::resource('performances', BrandPerformanceController::class)->only(['index']);
    Route::post('performances/{performance}/summarize', [BrandPerformanceController::class, 'summarize'])->name('performances.summarize');

    Route::get('settings/legal-pages', [BrandLegalPageController::class, 'edit'])->name('legal-pages.edit');

    // --- Write: gated (temporary: test@example.com only) ---

    Route::middleware('can:edit-brand-health')->group(function () {
        Route::resource('accounts', BrandAccountController::class)->only(['store', 'update', 'destroy']);

        Route::resource('cycles', BrandCycleController::class)->only(['store', 'update', 'destroy']);

        Route::resource('score-buckets', BrandScoreBucketController::class)->only(['store', 'update', 'destroy']);
        Route::resource('label-buckets', BrandLabelBucketController::class)->only(['store', 'update', 'destroy']);
        Route::put('formula-weights/{aggregate}', [BrandFormulaWeightController::class, 'update'])->name('formula-weights.update');

        Route::resource('performances', BrandPerformanceController::class)->only(['store', 'update', 'destroy']);

        Route::put('settings/legal-pages/{legalPage}', [BrandLegalPageController::class, 'update'])->name('legal-pages.update');
    });
});
