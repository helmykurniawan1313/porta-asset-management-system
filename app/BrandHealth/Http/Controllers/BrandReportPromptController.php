<?php

namespace App\BrandHealth\Http\Controllers;

use App\Http\Controllers\Controller;

use App\BrandHealth\Models\BrandContentInsight;
use App\BrandHealth\Models\BrandCycle;
use App\Models\User;
use App\BrandHealth\Models\BrandFormulaWeight;
use App\BrandHealth\Models\BrandLabelBucket;
use App\BrandHealth\Models\BrandPerformance;
use App\BrandHealth\Models\BrandScoreBucket;
use App\BrandHealth\Services\MetricCalculator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View as Response;

/**
 * Report Prompt Generator — super admin only. Lets the admin pick a scope
 * (month + optional PM) and which data sources to include (Cycles, Reels/
 * Video, Content Insights, Employees), then builds one big text prompt
 * carrying the real numbers in a structured, labeled format — meant to be
 * pasted into an AI chat (Claude/ChatGPT/etc.) to generate a PPTX report
 * with illustrations/graphs, WITHOUT this app making any AI API call itself.
 * Deliberately just text generation: no AI spend, no chart/image rendering
 * here — the admin runs it manually wherever they want.
 */
class BrandReportPromptController extends Controller
{
    public function index(): Response
    {
        return view('brand-health.report-prompt.index', [
            'accountDepartmentEmployees' => User::whereHas(
                'department',
                fn ($query) => $query->whereIn('name', ['Accounts', 'Accounts Manager'])
            )->orderBy('name')->get(['id', 'name']),
            // Portable distinct-months list (no DATE_FORMAT — not supported by
            // the SQLite connection the test suite runs on).
            'months' => BrandCycle::query()
                ->pluck('cycle_start_date')
                ->map(fn ($date) => $date->format('Y-m'))
                ->unique()
                ->sort()
                ->values(),
        ]);
    }

    public function generate(Request $request, MetricCalculator $calculator): JsonResponse
    {
        $data = $request->validate([
            'month_from' => ['required', 'string', 'regex:/^\d{4}-\d{2}$/'],
            'month_to' => ['nullable', 'string', 'regex:/^\d{4}-\d{2}$/'],
            'project_manager_id' => ['nullable', 'integer', 'exists:users,id'],
            'sources' => ['required', 'array', 'min:1'],
            'sources.*' => ['string', 'in:cycles,performances,content_insights,employees'],
        ]);

        $monthFrom = $data['month_from'];
        $monthTo = $data['month_to'] ?? $monthFrom;
        if ($monthTo < $monthFrom) {
            [$monthFrom, $monthTo] = [$monthTo, $monthFrom];
        }
        $pmId = $data['project_manager_id'] ?? null;
        $sources = $data['sources'];
        $pm = $pmId ? User::find($pmId) : null;

        $sections = [];

        if (in_array('cycles', $sources, true)) {
            $sections['cycles'] = $this->cyclesSection($monthFrom, $monthTo, $pmId, $calculator);
        }
        if (in_array('performances', $sources, true)) {
            $sections['performances'] = $this->performancesSection($monthFrom, $monthTo, $pmId);
        }
        if (in_array('content_insights', $sources, true)) {
            $sections['content_insights'] = $this->contentInsightsSection($monthFrom, $monthTo, $pmId);
        }
        if (in_array('employees', $sources, true)) {
            $sections['employees'] = $this->employeesSection($pmId);
        }

        $prompt = $this->buildPrompt($monthFrom, $monthTo, $pm, $sections);

        return response()->json(['prompt' => $prompt]);
    }

    private function cyclesSection(string $monthFrom, string $monthTo, ?int $pmId, MetricCalculator $calculator): array
    {
        $scoreBuckets = BrandScoreBucket::all();
        $labelBuckets = BrandLabelBucket::all();
        $formulaWeights = BrandFormulaWeight::all();

        $cycles = BrandCycle::with('account', 'projectManager')
            ->when($pmId, fn ($query) => $query->where('project_manager_id', $pmId))
            ->get()
            ->filter(function (BrandCycle $cycle) use ($monthFrom, $monthTo) {
                $month = $cycle->cycle_start_date->format('Y-m');

                return $month >= $monthFrom && $month <= $monthTo;
            })
            ->map(function (BrandCycle $cycle) use ($calculator, $scoreBuckets, $labelBuckets, $formulaWeights) {
                $scores = $calculator->calculate($cycle, $scoreBuckets, $labelBuckets, $formulaWeights);

                return [
                    'account' => $cycle->account->name ?? '—',
                    'platform' => $cycle->platform === 'tiktok' ? 'TikTok' : 'Instagram',
                    'pm' => $cycle->projectManager->name ?? '—',
                    'period' => $cycle->cycle_start_date->format('M j').' – '.$cycle->cycle_end_date->format('M j, Y'),
                    'start_follower' => $cycle->start_follower,
                    'end_follower' => $cycle->end_follower,
                    'reach' => $cycle->reach,
                    'views' => $cycle->views,
                    'engagement' => $cycle->engagement,
                    'ads_currency' => $cycle->ads_currency,
                    'reach_views_ads_used' => (bool) $cycle->reach_views_ads_used,
                    'reach_views_ads_spend' => (float) $cycle->reach_views_ads_spend,
                    'engagement_ads_used' => (bool) $cycle->engagement_ads_used,
                    'engagement_ads_spend' => (float) $cycle->engagement_ads_spend,
                    'growth_rate' => round($scores['growth_rate'], 2),
                    'visibility_rate' => round($scores['visibility_rate'], 2),
                    'engagement_score' => round($scores['engagement_score'], 2),
                    'health_rate' => round($scores['health_rate'], 2),
                    'health_label' => $scores['health_label'],
                ];
            })
            ->values();

        return [
            'title' => 'Cycles (Growth / Visibility / Engagement / Health)',
            'rows' => $cycles->all(),
        ];
    }

    private function performancesSection(string $monthFrom, string $monthTo, ?int $pmId): array
    {
        $rows = BrandPerformance::with('account', 'projectManager', 'conceptor', 'editor')
            ->when($pmId, fn ($query) => $query->where('project_manager_id', $pmId))
            ->get()
            ->filter(function (BrandPerformance $p) use ($monthFrom, $monthTo) {
                if (! $p->post_date) {
                    return false;
                }
                $month = $p->post_date->format('Y-m');

                return $month >= $monthFrom && $month <= $monthTo;
            })
            ->map(fn (BrandPerformance $p) => [
                'account' => $p->account->name ?? '—',
                'platform' => $p->platform === 'tiktok' ? 'TikTok' : 'Instagram',
                'content_type' => $p->ig_media_product_type,
                'pm' => $p->projectManager->name ?? '—',
                'conceptor' => $p->conceptor->name ?? '—',
                'editor' => $p->editor->name ?? '—',
                'post_date' => $p->post_date?->format('M j, Y'),
                'preview_date' => $p->preview_date?->format('M j, Y'),
                'followers' => $p->followers,
                'total_views_h7' => $p->total_views_h7,
                'ads' => $p->ads ? 'Yes' : 'No',
                'notes' => $p->notes,
            ])
            ->values();

        return [
            'title' => 'Reels / Video Performance (Views H+7)',
            'rows' => $rows->all(),
        ];
    }

    private function contentInsightsSection(string $monthFrom, string $monthTo, ?int $pmId): array
    {
        $rows = BrandContentInsight::with('account', 'cycle.projectManager')
            ->get()
            ->filter(function (BrandContentInsight $ci) use ($monthFrom, $monthTo) {
                if (! $ci->cycle) {
                    return false;
                }
                $month = $ci->cycle->cycle_start_date->format('Y-m');

                return $month >= $monthFrom && $month <= $monthTo;
            })
            ->when($pmId, fn ($rows) => $rows->filter(fn (BrandContentInsight $ci) => $ci->cycle->project_manager_id === $pmId))
            ->map(fn (BrandContentInsight $ci) => [
                'account' => $ci->account->name ?? '—',
                'pm' => $ci->cycle->projectManager->name ?? '—',
                'viewers_posts' => $ci->viewers_posts,
                'viewers_reels' => $ci->viewers_reels,
                'viewers_story' => $ci->viewers_story,
                'interactions_posts' => $ci->interactions_posts,
                'interactions_reels' => $ci->interactions_reels,
                'interactions_story' => $ci->interactions_story,
            ])
            ->values();

        return [
            'title' => 'Content Insights (Viewers & Interactions by content type)',
            'rows' => $rows->all(),
        ];
    }

    private function employeesSection(?int $pmId): array
    {
        $employees = User::with('department')
            ->when($pmId, fn ($query) => $query->where('id', $pmId))
            ->orderBy('name')
            ->get()
            ->map(fn (User $e) => [
                'name' => $e->name,
                'position' => $e->job_level,
                'department' => $e->department->name ?? '—',
            ])
            ->values();

        return [
            'title' => 'Employees',
            'rows' => $employees->all(),
        ];
    }

    private function buildPrompt(string $monthFrom, string $monthTo, ?User $pm, array $sections): string
    {
        $fromLabel = \Carbon\Carbon::createFromFormat('Y-m', $monthFrom)->format('F Y');
        $periodLabel = $fromLabel;
        if ($monthTo !== $monthFrom) {
            $toLabel = \Carbon\Carbon::createFromFormat('Y-m', $monthTo)->format('F Y');
            $periodLabel = "{$fromLabel} – {$toLabel}";
        }

        $lines = [];
        $lines[] = 'You are building a PowerPoint (PPTX) performance report for a social media management agency, using the raw data below.';
        $lines[] = '';
        $lines[] = 'SCOPE';
        $lines[] = "- Reporting period: {$periodLabel}";
        $lines[] = '- Project Manager: '.($pm ? $pm->name : 'All PMs');
        $lines[] = '';
        $lines[] = 'INSTRUCTIONS';
        $lines[] = '- Create one slide deck summarizing the data below, grouped by account where applicable.';
        $lines[] = '- Use charts/graphs (bar or line) to visualize numeric trends (e.g. Health score, Growth rate, Views) rather than just listing numbers.';
        $lines[] = '- Add a short narrative/insight callout per section — not just raw numbers restated.';
        $lines[] = '- Use a clean, professional slide design with illustrations/icons where it helps communicate the data.';
        $lines[] = '- Health labels (SIP/BAGUS/CUKUP/KURANG/PARAH) are best-to-worst status tiers — color-code them consistently (e.g. green=SIP, red=PARAH) across all slides.';
        $lines[] = '';
        $lines[] = 'DATA';

        foreach ($sections as $key => $section) {
            $lines[] = '';
            $lines[] = '## '.$section['title'];

            if (empty($section['rows'])) {
                $lines[] = '(No data for this scope.)';

                continue;
            }

            $lines[] = json_encode($section['rows'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        }

        return implode("\n", $lines);
    }
}
