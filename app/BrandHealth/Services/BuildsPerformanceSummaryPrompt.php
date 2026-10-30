<?php

namespace App\BrandHealth\Services;

use App\BrandHealth\Models\BrandPerformance;

trait BuildsPerformanceSummaryPrompt
{
    protected function performanceSystemPrompt(): string
    {
        return 'You are a marketing analyst reviewing the performance of a single social media post.

For every summary, cover three things in plain prose (no markdown, no headers, no bullet points):
1. What happened - how the post performed (views, follower category, status), and whether the engagement metrics (if available) stand out.
2. Why it matters - what this performance says about the post itself or the account it belongs to.
3. What to do next - one concrete, specific recommendation for future posts, tied to what worked or fell short here.

Default length is 3-5 sentences. Do not just restate numbers - always name the underlying takeaway and pair it with an action.

If the user gives additional instructions, follow them for tone, length, or format, but still include at least a brief nod to the takeaway and the recommended action unless the user explicitly says to omit them.

Write your final answer in Bahasa Indonesia, in natural, fluent, professional marketing language. Do all your reasoning in English internally, but the output text must be entirely in Bahasa Indonesia.';
    }

    protected function buildPerformancePrompt(BrandPerformance $performance, ?string $customPrompt = null): string
    {
        $snapshot = $performance->igSnapshots->first();

        $data = <<<PROMPT
        Account: {$performance->account->name}
        Post Date: {$performance->post_date->format('M j, Y')}
        Ads used: {$this->yesNo($performance->ads)}

        Followers: {$this->nullableNumber($performance->followers)} ({$this->nullableLabel($performance->follower_category)})
        Views H+7: {$this->nullableNumber($performance->total_views_h7)} ({$this->nullableLabel($performance->views_status)})
        PROMPT;

        if ($snapshot) {
            $data .= "\n\nInstagram insights (as of {$snapshot->fetched_at->format('M j, Y')}):";
            $data .= $this->metricLine('Reach', $snapshot->reach);
            $data .= $this->metricLine('Likes', $snapshot->likes);
            $data .= $this->metricLine('Comments', $snapshot->comments);
            $data .= $this->metricLine('Shares', $snapshot->shares);
            $data .= $this->metricLine('Saved', $snapshot->saved);
            $data .= $this->metricLine('Total interactions', $snapshot->total_interactions);
            $data .= $this->metricLine('Views', $snapshot->views);
        }

        if ($customPrompt) {
            $data .= "\n\nAdditional instructions from the user: {$customPrompt}";
        }

        return $data;
    }

    private function yesNo(bool $value): string
    {
        return $value ? 'Yes' : 'No';
    }

    private function nullableNumber(?int $value): string
    {
        return $value === null ? 'not recorded' : number_format($value);
    }

    private function nullableLabel(?string $value): string
    {
        return $value ?? 'no data';
    }

    private function metricLine(string $label, ?int $value): string
    {
        return $value === null ? '' : "\n- {$label}: ".number_format($value);
    }
}
