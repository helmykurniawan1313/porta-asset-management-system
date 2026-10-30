<?php

namespace App\BrandHealth\Services;

use App\BrandHealth\Models\BrandCycle;
use App\BrandHealth\Models\BrandPerformance;
use Illuminate\Support\Collection;

interface SummaryProvider
{
    public function summarize(BrandCycle $cycle, array $scores, ?string $customPrompt = null): string;

    public function summarizeFiltered(Collection $cycles, array $filterDescription, ?string $customPrompt = null): string;

    public function summarizePerformance(BrandPerformance $performance, ?string $customPrompt = null): string;

    public function summarizeFilteredPerformances(Collection $performances, array $filterDescription, ?string $customPrompt = null): string;
}
