<?php

namespace App\BrandHealth\Services;

class SummaryProviderResolver
{
    public const PROVIDERS = ['groq', 'gemini'];

    public function resolve(?string $provider = null): SummaryProvider
    {
        $provider ??= config('services.brand_health.ai_summary.provider', 'groq');

        return match ($provider) {
            'gemini' => app(GeminiSummaryService::class),
            default => app(GroqSummaryService::class),
        };
    }
}
