{{-- One platform × tier threshold set in the score-tier matrix; clicking opens its editor below. --}}
@php
    $tiers = collect($g['buckets']);
    $top = $tiers->first();
@endphp
<button type="button" class="sb-cell" data-bs-toggle="collapse" data-bs-target="#{{ $target }}" aria-expanded="false">
    <div class="sb-range">@foreach($segments($tiers, 'min_rate') as $s)<span style="left: {{ $s['start'] }}%; width: {{ $s['width'] }}%; background: {{ $s['tone'][1] }}"></span>@endforeach</div>
    <div class="d-flex justify-content-between small mt-1">
        <span class="bh-muted">{{ $tiers->count() }} tier{{ $tiers->count() === 1 ? '' : 's' }}</span>
        @if($top)<span class="bh-num fw-semibold">≥{{ $fmt($top['min_rate']) }} → {{ $fmt($top['score']) }}</span>@endif
    </div>
</button>
