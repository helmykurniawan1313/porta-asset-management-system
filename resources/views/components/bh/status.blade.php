@props(['status' => null])
@php
    /*
     * Blade port of Brand Matrix's StatusBadge.vue: a label's colour comes from
     * the LabelBucket whose text matches (case-insensitive), falling back to a
     * rank-based tone among that metric's buckets when no colour is stored.
     */
    $buckets = $bhLabelBuckets ?? collect();
    $tones = ['sip', 'bagus', 'cukup', 'kurang', 'parah'];
    $bg = 'var(--bh-border)';
    $ink = 'var(--bh-ink-muted)';
    if ($status) {
        $match = $buckets->first(fn ($b) => strcasecmp((string) $b->label, (string) $status) === 0);
        if ($match) {
            $rank = $buckets->where('metric', $match->metric)
                ->sortByDesc(fn ($b) => $b->min_score ?? -INF)->values()
                ->search(fn ($b) => $b->id === $match->id);
            if ($match->color === 'custom' && $match->custom_color_bg && $match->custom_color_ink) {
                [$bg, $ink] = [$match->custom_color_bg, $match->custom_color_ink];
            } else {
                $tone = in_array($match->color, $tones, true) ? $match->color : $tones[min(max((int) $rank, 0), 4)];
                [$bg, $ink] = ["var(--status-$tone-bg)", "var(--status-$tone-ink)"];
            }
        }
    }
@endphp
<span {{ $attributes->merge(['class' => 'bh-status']) }} style="background-color: {{ $bg }}; color: {{ $ink }}">
    <span class="bh-status-dot" style="background-color: {{ $ink }}"></span>{{ $status ?? 'No data' }}
</span>
