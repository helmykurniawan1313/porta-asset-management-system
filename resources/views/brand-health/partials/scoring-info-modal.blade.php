{{--
    "How these statuses are scored" — Blade port of ScoringInfoModal.vue.
    @include('brand-health.partials.scoring-info-modal', ['metrics' => ['health'], 'id' => 'scoring-info'])
    Open with data-bs-toggle="modal" data-bs-target="#scoring-info".
--}}
@php
    $id = $id ?? 'scoring-info';
    $metricLabels = [
        'growth' => 'Growth', 'visibility' => 'Visibility', 'engagement' => 'Engagement', 'health' => 'Health',
        'views' => 'Views', 'followers' => 'Followers', 'account_growth' => 'Follower Growth',
        'views_change' => 'Views (vs. last cycle)', 'reach_change' => 'Reach (vs. last cycle)',
        'engagement_change' => 'Engagement (vs. last cycle)',
    ];
    $tones = ['sip', 'bagus', 'cukup', 'kurang', 'parah'];
    $compact = function ($v) {
        $n = (float) $v;
        if (abs($n) < 1000) return rtrim(rtrim(number_format($n, 2, '.', ''), '0'), '.');
        foreach ([[1e9, 'B'], [1e6, 'M'], [1e3, 'K']] as [$div, $suffix]) {
            if (abs($n) >= $div) return rtrim(rtrim(number_format($n / $div, 1, '.', ''), '0'), '.').$suffix;
        }
    };
    $ladders = collect($metrics)->map(fn ($m) => [
        'title' => $metricLabels[$m] ?? $m,
        'tiers' => ($bhLabelBuckets ?? collect())->where('metric', $m)->sortByDesc(fn ($b) => $b->min_score ?? -INF)->values(),
    ])->filter(fn ($l) => $l['tiers']->isNotEmpty());
@endphp
<div class="modal fade" id="{{ $id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title fw-bold">How these statuses are scored</h5>
                    <small class="bh-muted">Each label's threshold, best to worst. Set in Scoring Buckets.</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                @forelse($ladders as $i => $ladder)
                    <div class="{{ $loop->first ? '' : 'mt-4 pt-4 border-top' }}">
                        <div class="bh-stat-label mb-2">{{ $ladder['title'] }}</div>
                        @foreach($ladder['tiers'] as $j => $tier)
                            @php
                                $ink = $tier->color === 'custom' && $tier->custom_color_ink
                                    ? $tier->custom_color_ink
                                    : 'var(--status-'.(in_array($tier->color, $tones, true) ? $tier->color : $tones[min($j, 4)]).'-ink)';
                                $prev = $ladder['tiers'][$j - 1] ?? null;
                            @endphp
                            <div class="d-flex justify-content-between align-items-center py-1">
                                <span class="d-flex align-items-center gap-2"><span class="bh-status-dot" style="background: {{ $ink }}"></span>{{ $tier->label }}</span>
                                <span class="bh-faint bh-num">
                                    @if($tier->min_score === null)
                                        {{ $prev ? '< '.$compact($prev->min_score ?? 0) : 'any value' }}
                                    @else
                                        ≥ {{ $compact($tier->min_score) }}
                                    @endif
                                </span>
                            </div>
                        @endforeach
                    </div>
                @empty
                    <p class="bh-empty mb-0">No scoring tiers configured yet.</p>
                @endforelse
            </div>
            <div class="modal-footer">
                <a href="{{ route('brand-health.score-buckets.index') }}" class="btn btn-primary">Edit in Scoring Buckets</a>
            </div>
        </div>
    </div>
</div>
