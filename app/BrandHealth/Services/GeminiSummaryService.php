<?php

namespace App\BrandHealth\Services;

use App\BrandHealth\Models\BrandCycle;
use App\BrandHealth\Models\BrandPerformance;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class GeminiSummaryService implements SummaryProvider
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
        $apiKey = config('services.brand_health.gemini.key');

        if (! $apiKey) {
            throw new RuntimeException('Gemini API key is not configured. Set GEMINI_API_KEY in your .env file.');
        }

        $model = config('services.brand_health.gemini.model', 'gemini-2.0-flash');

        $response = Http::timeout(30)
            ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}", [
                'systemInstruction' => [
                    'parts' => [['text' => $systemPrompt]],
                ],
                'contents' => [
                    ['role' => 'user', 'parts' => [['text' => $prompt]]],
                ],
                'generationConfig' => [
                    'temperature' => 0.4,
                    'maxOutputTokens' => 2048,
                ],
            ]);

        if ($response->failed()) {
            throw new RuntimeException(
                'Gemini API request failed: '.($response->json('error.message') ?? $response->status())
            );
        }

        $summary = trim((string) $response->json('candidates.0.content.parts.0.text'));
        $finishReason = $response->json('candidates.0.finishReason');

        if ($summary === '' && $finishReason === 'MAX_TOKENS') {
            throw new RuntimeException('Gemini API ran out of tokens before producing a summary. Try again or shorten your custom instructions.');
        }

        if ($summary === '') {
            throw new RuntimeException('Gemini API returned an empty summary.');
        }

        return $summary;
    }
}
