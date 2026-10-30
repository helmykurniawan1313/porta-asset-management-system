<?php

namespace App\BrandHealth\Services;

use App\BrandHealth\Models\BrandCycle;

trait BuildsSummaryPrompt
{
    protected function systemPrompt(): string
    {
        return 'You are a marketing analyst reviewing brand health data for a social media account.

For every summary, cover three things in plain prose (no markdown, no headers, no bullet points):
1. What changed - the key metric movements and which score categories are weak (KURANG) or strong (BAGUS).
2. Why it matters - the likely cause or business implication of that change (e.g. declining followers with low visibility suggests reach/discovery issues, not a content quality problem).
3. What to do next - one concrete, specific recommendation tied to the weakest score (e.g. increase posting frequency, run a reach campaign, address churn), not generic advice like "improve engagement."

Default length is 4-6 sentences. Do not just restate numbers - always name the underlying problem and pair it with an action.

If the user gives additional instructions, follow them for tone, length, or format (e.g. "make it upbeat" or "keep it to 2 sentences"), but still include at least a brief nod to the problem and the recommended action unless the user explicitly says to omit them.

Write your final answer in Bahasa Indonesia, in natural, fluent, professional marketing language. Do all your reasoning in English internally, but the output text must be entirely in Bahasa Indonesia.';
    }

    protected function buildPrompt(BrandCycle $cycle, array $scores, ?string $customPrompt = null): string
    {
        $data = <<<PROMPT
        Account: {$cycle->account->name}
        Cycle: {$cycle->cycle_start_date->format('M j, Y')} to {$cycle->cycle_end_date->format('M j, Y')}

        Followers: {$cycle->start_follower} -> {$cycle->end_follower} (growth: {$scores['growth']}, rate: {$scores['growth_rate']}%)
        Reach: {$cycle->reach}, Views: {$cycle->views}, Engagement: {$cycle->engagement}

        Scores (0-100 scale):
        - Growth score: {$scores['growth_score']}
        - Visibility: {$scores['visibility_rate']} ({$scores['visibility_label']})
        - Engagement: {$scores['engagement_score']} ({$scores['engagement_label']})
        - Health: {$scores['health_rate']} ({$scores['health_label']})
        PROMPT;

        if ($customPrompt) {
            $data .= "\n\nAdditional instructions from the user: {$customPrompt}";
        }

        return $data;
    }
}
