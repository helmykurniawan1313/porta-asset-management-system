<?php

namespace App\BrandHealth\Services;

use Illuminate\Support\Collection;

trait BuildsFilterSummaryPrompt
{
    protected function filterSystemPrompt(): string
    {
        return 'You are a marketing analyst reviewing a set of social media performance cycles (could be one client over several periods, or several clients).

For every summary, cover three things in plain prose (no markdown, no headers, no bullet points):
1. What changed - the overall trend across the cycles, which cycles stood out (best/worst), and which score categories are weak (KURANG/PARAH) or strong (SIP/BAGUS).
2. Why it matters - the likely cause or business implication of that trend.
3. What to do next - one concrete, specific recommendation tied to the weakest area, not generic advice.

Default length is 4-8 sentences. Do not just restate numbers - always name the underlying pattern and pair it with an action.

If the user gives additional instructions, follow them for tone, length, or format, but still include at least a brief nod to the trend and the recommended action unless the user explicitly says to omit them.

Write your final answer in Bahasa Indonesia, in natural, fluent, professional marketing language. Do all your reasoning in English internally, but the output text must be entirely in Bahasa Indonesia.';
    }

    protected function buildFilterPrompt(Collection $cycles, array $filterDescription, ?string $customPrompt = null): string
    {
        $blocks = $cycles->map(function ($entry) {
            $cycle = $entry['cycle'];
            $s = $entry['scores'];

            return sprintf(
                "- %s | %s to %s\n".
                '  Followers: %s -> %s (%+d, growth %s%%, score %s)'."\n".
                '  Reach: %s (reach rate %sx, score %s) | Views: %s (view rate %sx, score %s)'."\n".
                '  Engagement: %s (ER-of-reach %s%%, score %s | ER-of-followers %s%%, score %s)'."\n".
                '  Visibility: %s (%s) | Engagement: %s (%s) | Health: %s (%s)',
                $cycle->account->name,
                $cycle->cycle_start_date->format('M j, Y'),
                $cycle->cycle_end_date->format('M j, Y'),
                number_format($cycle->start_follower),
                number_format($cycle->end_follower),
                $s['growth'],
                round($s['growth_rate'], 1),
                round($s['growth_score'], 1),
                number_format($cycle->reach),
                round($s['reach_rate'], 2),
                round($s['reach_score'], 1),
                number_format($cycle->views),
                round($s['view_rate'], 2),
                round($s['view_score'], 1),
                number_format($cycle->engagement),
                round($s['er_reach_rate'], 2),
                round($s['er_reach_score'], 1),
                round($s['er_follower_rate'], 2),
                round($s['er_follower_score'], 1),
                round($s['visibility_rate'], 1),
                $s['visibility_label'],
                round($s['engagement_score'], 1),
                $s['engagement_label'],
                round($s['health_rate'], 1),
                $s['health_label'],
            );
        })->implode("\n\n");

        $avgHealth = round($cycles->avg(fn ($entry) => $entry['scores']['health_rate']), 1);

        $data = <<<PROMPT
        Filters applied: {$filterDescription['summary']}
        Number of cycles: {$cycles->count()}
        Average health rate: {$avgHealth}

        Cycles (oldest to newest, with raw metrics, per-metric rates/scores, and aggregate scores):
        {$blocks}
        PROMPT;

        if ($customPrompt) {
            $data .= "\n\nAdditional instructions from the user: {$customPrompt}";
        }

        return $data;
    }
}
