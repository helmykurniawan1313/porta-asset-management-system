<?php

namespace App\BrandHealth\Services;

use Illuminate\Support\Collection;

trait BuildsFilterPerformanceSummaryPrompt
{
    protected function filterPerformanceSystemPrompt(): string
    {
        return 'You are a marketing analyst reviewing a set of individual social media posts (could be one account or several).

For every summary, cover three things in plain prose (no markdown, no headers, no bullet points):
1. What changed - the overall pattern across the posts, which posts stood out (best/worst by views/status), and any notable followers trend.
2. Why it matters - the likely cause or business implication of that pattern.
3. What to do next - one concrete, specific recommendation tied to the weakest area, not generic advice.

Default length is 4-8 sentences. Do not just restate numbers - always name the underlying pattern and pair it with an action.

If the user gives additional instructions, follow them for tone, length, or format, but still include at least a brief nod to the pattern and the recommended action unless the user explicitly says to omit them.

Write your final answer in Bahasa Indonesia, in natural, fluent, professional marketing language. Do all your reasoning in English internally, but the output text must be entirely in Bahasa Indonesia.';
    }

    protected function buildFilterPerformancePrompt(Collection $performances, array $filterDescription, ?string $customPrompt = null): string
    {
        $blocks = $performances->map(function ($performance) {
            return sprintf(
                '- %s | %s | Ads: %s | Followers: %s (%s) | Views H+7: %s (%s)',
                $performance->account->name,
                $performance->post_date->format('M j, Y'),
                $performance->ads ? 'Yes' : 'No',
                $performance->followers !== null ? number_format($performance->followers) : 'not recorded',
                $performance->follower_category ?? 'no data',
                $performance->total_views_h7 !== null ? number_format($performance->total_views_h7) : 'not recorded',
                $performance->views_status ?? 'no data',
            );
        })->implode("\n");

        $withViews = $performances->filter(fn ($p) => $p->total_views_h7 !== null);
        $avgViews = $withViews->isEmpty() ? 'n/a' : number_format(round($withViews->avg('total_views_h7')));

        $data = <<<PROMPT
        Filters applied: {$filterDescription['summary']}
        Number of posts: {$performances->count()}
        Average Views H+7 (where recorded): {$avgViews}

        Posts (oldest to newest):
        {$blocks}
        PROMPT;

        if ($customPrompt) {
            $data .= "\n\nAdditional instructions from the user: {$customPrompt}";
        }

        return $data;
    }
}
