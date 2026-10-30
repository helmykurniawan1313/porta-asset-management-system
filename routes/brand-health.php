<?php

use App\BrandHealth\Http\Controllers\BrandAccountController;
use App\BrandHealth\Http\Controllers\BrandAccountGrowthController;
use App\BrandHealth\Http\Controllers\BrandAccountHealthController;
use App\BrandHealth\Http\Controllers\BrandAccountPerformanceController;
use App\BrandHealth\Http\Controllers\BrandContentInsightController;
use App\BrandHealth\Http\Controllers\BrandCycleController;
use App\BrandHealth\Http\Controllers\BrandDashboardController;
use App\BrandHealth\Http\Controllers\BrandEmployeeController;
use App\BrandHealth\Http\Controllers\BrandFormulaWeightController;
use App\BrandHealth\Http\Controllers\BrandInstagramController;
use App\BrandHealth\Http\Controllers\BrandLabelBucketController;
use App\BrandHealth\Http\Controllers\BrandLegalPageController;
use App\BrandHealth\Http\Controllers\BrandPerformanceController;
use App\BrandHealth\Http\Controllers\BrandReportPromptController;
use App\BrandHealth\Http\Controllers\BrandScoreBucketController;
use App\BrandHealth\Http\Controllers\BrandSiteSettingController;
use App\BrandHealth\Http\Controllers\BrandViewsTrackerController;
use App\BrandHealth\Http\Controllers\BrandViewsTrendController;
use App\BrandHealth\Http\Controllers\BrandWeeklyReportController;
use App\BrandHealth\Models\BrandLegalPage;
use Illuminate\Support\Facades\Route;

/*
 * Brand Health — ported from Brand Matrix Experimental Plus. Same routes as
 * Brand Matrix, under /brand-health with "brand-health." names.
 * Read: any signed-in user. Write: can:edit-brand-health (Brand Matrix's
 * "editor"). Site settings / report prompt: can:admin-brand-health.
 * Users, departments and employees are it-inventory's own, so Brand Matrix's
 * screens for those aren't ported (only the employee drill-down JSON is).
 */
Route::middleware('auth')->prefix('brand-health')->name('brand-health.')->group(function () {
    Route::get('/', [BrandDashboardController::class, 'index'])->name('dashboard');

    // --- Views Trend ---
    Route::get('views-trend', [BrandViewsTrendController::class, 'index'])->name('views-trend.index');
    Route::get('views-trend-pdf', [BrandViewsTrendController::class, 'pdf'])->name('views-trend.pdf');
    Route::get('views-trend-excel', [BrandViewsTrendController::class, 'exportExcel'])->name('views-trend.excel');
    Route::get('views-trend-ranking', [BrandViewsTrendController::class, 'ranking'])->name('views-trend.ranking');
    Route::get('views-trend-ranking-pdf', [BrandViewsTrendController::class, 'rankingPdf'])->name('views-trend.ranking.pdf');
    Route::get('views-trend-ranking-excel', [BrandViewsTrendController::class, 'rankingExcel'])->name('views-trend.ranking.excel');
    Route::get('views-trend-ranking/{employee}', [BrandViewsTrendController::class, 'personSeries'])->name('views-trend.ranking.person');
    Route::get('views-trend-ranking/{employee}/posts', [BrandViewsTrendController::class, 'personPosts'])->name('views-trend.ranking.person-posts');
    Route::get('views-trend/{account}', [BrandViewsTrendController::class, 'detail'])->name('views-trend.detail');
    Route::get('views-trend/{account}/cycles/{cycle}/posts', [BrandViewsTrendController::class, 'cyclePosts'])->name('views-trend.cycle-posts');

    // --- Accounts ---
    Route::resource('accounts', BrandAccountController::class)->only(['index']);
    Route::get('accounts-excel', [BrandAccountController::class, 'exportExcel'])->name('accounts.excel');
    Route::get('accounts/{account}/growth', [BrandAccountController::class, 'growth'])->name('accounts.growth');
    Route::post('accounts/{account}/pdf', [BrandAccountController::class, 'pdf'])->name('accounts.pdf');
    Route::post('accounts/{account}/summarize', [BrandAccountController::class, 'summarize'])->name('accounts.summarize');
    Route::get('accounts/{account}/instagram', [BrandInstagramController::class, 'show'])->name('accounts.instagram.show');
    Route::get('accounts/{account}/instagram/media-insights', [BrandInstagramController::class, 'mediaInsights'])->name('accounts.instagram.media-insights');
    Route::get('accounts/{account}/instagram/media', [BrandInstagramController::class, 'mediaList'])->name('accounts.instagram.media');

    Route::get('account-health', [BrandAccountHealthController::class, 'index'])->name('account-health.index');
    Route::get('account-growth', [BrandAccountGrowthController::class, 'index'])->name('account-growth.index');

    Route::get('account-performance', [BrandAccountPerformanceController::class, 'index'])->name('account-performance.index');
    Route::get('account-performance/{account}/trend', [BrandAccountPerformanceController::class, 'trend'])->name('account-performance.trend');
    Route::get('account-performance-pdf', [BrandAccountPerformanceController::class, 'pdf'])->name('account-performance.pdf');
    Route::get('account-performance-excel', [BrandAccountPerformanceController::class, 'exportExcel'])->name('account-performance.excel');

    Route::get('views-trackers', [BrandViewsTrackerController::class, 'index'])->name('views-trackers.index');

    // --- Cycles ---
    Route::resource('cycles', BrandCycleController::class)->only(['index']);
    Route::get('accounts/{account}/neighboring-cycle', [BrandCycleController::class, 'neighboring'])->name('cycles.neighboring');
    Route::get('cycles-pdf', [BrandCycleController::class, 'pdf'])->name('cycles.pdf');
    Route::get('cycles-excel', [BrandCycleController::class, 'exportExcel'])->name('cycles.excel');
    Route::get('cycles/{cycle}/pdf', [BrandCycleController::class, 'pdfSingle'])->name('cycles.pdf-single');
    Route::post('cycles/{cycle}/summarize', [BrandCycleController::class, 'summarize'])->name('cycles.summarize');
    Route::post('cycles-summarize', [BrandCycleController::class, 'summarizeFiltered'])->name('cycles.summarize-filtered');
    Route::get('cycles-ads-ranking', [BrandCycleController::class, 'adsRanking'])->name('cycles.ads-ranking');
    Route::get('cycles-ads-ranking-pdf', [BrandCycleController::class, 'adsRankingPdf'])->name('cycles.ads-ranking.pdf');
    Route::get('cycles-ads-ranking-excel', [BrandCycleController::class, 'adsRankingExcel'])->name('cycles.ads-ranking.excel');

    // --- Weekly reports ---
    Route::resource('weekly-reports', BrandWeeklyReportController::class)->only(['index']);
    Route::get('accounts/{account}/neighboring-weekly-report', [BrandWeeklyReportController::class, 'neighboring'])->name('weekly-reports.neighboring');

    // --- Scoring settings ---
    Route::resource('score-buckets', BrandScoreBucketController::class)->only(['index']);

    // --- Content insights ---
    Route::get('content-insights', [BrandContentInsightController::class, 'index'])->name('content-insights.index');
    Route::get('content-insights-pdf', [BrandContentInsightController::class, 'pdf'])->name('content-insights.pdf');
    Route::get('content-insights-excel', [BrandContentInsightController::class, 'exportExcel'])->name('content-insights.excel');
    Route::get('accounts/{account}/insight-cycles', [BrandContentInsightController::class, 'cycles'])->name('content-insights.cycles');

    // --- Employee drill-down (posts credited to a PM / conceptor) ---
    Route::get('employees/{employee}/posts', [BrandEmployeeController::class, 'posts'])->name('employees.posts');
    Route::get('employees/{employee}/series', [BrandEmployeeController::class, 'series'])->name('employees.series');

    // --- Performance ---
    Route::resource('performances', BrandPerformanceController::class)->only(['index']);
    Route::get('performances/{performance}/pdf', [BrandPerformanceController::class, 'pdf'])->name('performances.pdf');
    Route::post('performances/{performance}/summarize', [BrandPerformanceController::class, 'summarize'])->name('performances.summarize');
    Route::get('performances-pdf', [BrandPerformanceController::class, 'pdfFiltered'])->name('performances.pdf-filtered');
    Route::get('performances-excel', [BrandPerformanceController::class, 'exportExcel'])->name('performances.excel');
    Route::post('performances-summarize', [BrandPerformanceController::class, 'summarizeFiltered'])->name('performances.summarize-filtered');

    Route::get('settings/legal-pages', [BrandLegalPageController::class, 'edit'])->name('legal-pages.edit');

    // --- Write: Brand Matrix "editor" ---
    Route::middleware('can:edit-brand-health')->group(function () {
        Route::resource('accounts', BrandAccountController::class)->only(['store', 'update', 'destroy']);
        Route::post('accounts/{account}/instagram/connect', [BrandInstagramController::class, 'connect'])->name('accounts.instagram.connect');
        Route::delete('accounts/{account}/instagram/disconnect', [BrandInstagramController::class, 'disconnect'])->name('accounts.instagram.disconnect');
        Route::get('accounts/{account}/instagram/oauth/redirect', [BrandInstagramController::class, 'redirect'])->name('accounts.instagram.oauth.redirect');
        Route::post('accounts/{account}/instagram/import', [BrandInstagramController::class, 'import'])->name('accounts.instagram.import');

        Route::resource('cycles', BrandCycleController::class)->only(['store', 'update', 'destroy']);
        Route::resource('weekly-reports', BrandWeeklyReportController::class)->only(['store', 'update', 'destroy']);

        Route::resource('score-buckets', BrandScoreBucketController::class)->only(['store', 'update', 'destroy']);
        Route::post('score-buckets-recalculate', [BrandScoreBucketController::class, 'recalculate'])->name('score-buckets.recalculate');

        Route::post('content-insights', [BrandContentInsightController::class, 'store'])->name('content-insights.store');
        Route::put('content-insights/{contentInsight}', [BrandContentInsightController::class, 'update'])->name('content-insights.update');
        Route::delete('content-insights/{contentInsight}', [BrandContentInsightController::class, 'destroy'])->name('content-insights.destroy');

        Route::resource('label-buckets', BrandLabelBucketController::class)->only(['store', 'update', 'destroy']);
        Route::put('formula-weights/{aggregate}', [BrandFormulaWeightController::class, 'update'])->name('formula-weights.update');

        Route::resource('performances', BrandPerformanceController::class)->only(['store', 'update', 'destroy']);
        Route::post('performances/{performance}/instagram/link', [BrandPerformanceController::class, 'linkInstagram'])->name('performances.instagram.link');
        Route::delete('performances/{performance}/instagram/link', [BrandPerformanceController::class, 'unlinkInstagram'])->name('performances.instagram.unlink');

        Route::put('settings/legal-pages/{legalPage}', [BrandLegalPageController::class, 'update'])->name('legal-pages.update');
    });

    // --- Super admin ---
    Route::middleware('can:admin-brand-health')->group(function () {
        Route::get('settings/site', [BrandSiteSettingController::class, 'edit'])->name('site-settings.edit');
        Route::put('settings/site', [BrandSiteSettingController::class, 'update'])->name('site-settings.update');

        Route::get('report-prompt', [BrandReportPromptController::class, 'index'])->name('report-prompt.index');
        Route::post('report-prompt/generate', [BrandReportPromptController::class, 'generate'])->name('report-prompt.generate');
    });
});

// Public (Meta requires these to be reachable without login).
Route::prefix('brand-health')->name('brand-health.')->group(function () {
    Route::get('instagram/oauth/callback', [BrandInstagramController::class, 'callback'])->name('instagram.oauth.callback');

    Route::get('privacy-policy', fn () => view('brand-health.legal.show', [
        'page' => BrandLegalPage::where('slug', BrandLegalPage::PRIVACY_POLICY)->firstOrFail(),
    ]))->name('legal.privacy-policy');

    Route::get('data-deletion', fn () => view('brand-health.legal.show', [
        'page' => BrandLegalPage::where('slug', BrandLegalPage::DATA_DELETION)->firstOrFail(),
    ]))->name('legal.data-deletion');
});
