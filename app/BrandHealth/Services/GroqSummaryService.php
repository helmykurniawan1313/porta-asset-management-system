<?php

namespace App\BrandHealth\Services;

use App\BrandHealth\Models\BrandCycle;
use App\BrandHealth\Models\BrandPerformance;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GroqSummaryService implements SummaryProvider
{
    use BuildsSummaryPrompt;
    use BuildsFilterSummaryPrompt;
    use BuildsPerformanceSummaryPrompt;
    use BuildsFilterPerformanceSummaryPrompt;

    public function summarize(BrandCycle $cycle, array $scores, ?string $customPrompt = null): string
    {
        $prompt = $this->buildPrompt($cycle, $scores, $customPrompt);

        return $this->request($this->systemPrompt(), $prompt);
    }

    public function summarizeFiltered(Collection $cycles, array $filterDescription, ?string $customPrompt = null): string
    {
        $prompt = $this->buildFilterPrompt($cycles, $filterDescription, $customPrompt);

        return $this->request($this->filterSystemPrompt(), $prompt);
    }

    public function summarizePerformance(BrandPerformance $performance, ?string $customPrompt = null): string
    {
        $prompt = $this->buildPerformancePrompt($performance, $customPrompt);

        return $this->request($this->performanceSystemPrompt(), $prompt);
    }

    public function summarizeFilteredPerformances(Collection $performances, array $filterDescription, ?string $customPrompt = null): string
    {
        $prompt = $this->buildFilterPerformancePrompt($performances, $filterDescription, $customPrompt);

        return $this->request($this->filterPerformanceSystemPrompt(), $prompt);
    }

    private function request(string $systemPrompt, string $prompt): string
    {
        $apiKey = config('services.brand_health.groq.key');

        if (! $apiKey) {
            throw new RuntimeException('Groq API key is not configured. Set BRAND_HEALTH_GROQ_API_KEY in your .env file.');
        }

        $response = Http::withToken($apiKey)
            ->timeout(30)
            ->post('https://api.groq.com/openai/v1/chat/completions', [
                'model' => config('services.brand_health.groq.model', 'llama-3.3-70b-versatile'),
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => 0.4,
                'max_tokens' => 700,
            ]);

        if ($response->failed()) {
            throw new RuntimeException(
                'Groq API request failed: '.($response->json('error.message') ?? $response->status())
            );
        }

        $summary = trim((string) $response->json('choices.0.message.content'));
        $finishReason = $response->json('choices.0.finish_reason');

        if ($summary === '' && $finishReason === 'length') {
            throw new RuntimeException('Groq API ran out of tokens before producing a summary. Try again or shorten your custom instructions.');
        }

        if ($summary === '') {
            throw new RuntimeException('Groq API returned an empty summary.');
        }

        return $summary;
    }
}
