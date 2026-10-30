<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #12181a; }
        h1 { font-size: 18px; margin: 0; }
        .meta { color: #566469; font-size: 11px; margin-top: 4px; }
        .header { border-bottom: 2px solid #12181a; padding-bottom: 10px; margin-bottom: 16px; }
        .section-title { font-size: 10px; text-transform: uppercase; color: #8b979b; margin: 18px 0 6px; letter-spacing: 0.5px; }
        .chart-block { margin-bottom: 18px; page-break-inside: avoid; }
        .chart-block img { width: 100%; max-height: 260px; object-fit: contain; }
        .ai-summary { margin-top: 6px; padding: 12px 14px; background: #f2f6f5; border-left: 3px solid #0f766e; }
        .ai-summary h2 { font-size: 11px; text-transform: uppercase; letter-spacing: 0.04em; color: #0f766e; margin: 0 0 6px; }
        .ai-summary p { margin: 0; line-height: 1.5; }
        .kpis { width: 100%; margin-top: 4px; }
        .kpis td { width: 20%; padding: 8px; border: 1px solid #d8dedc; vertical-align: top; }
        .kpis .label { font-size: 9px; text-transform: uppercase; color: #8b979b; display: block; }
        .kpis .value { font-size: 17px; font-weight: bold; margin-top: 3px; }
        .kpis .delta-up { font-size: 10px; font-weight: bold; color: #146c48; margin-top: 2px; }
        .kpis .delta-down { font-size: 10px; font-weight: bold; color: #a1282a; margin-top: 2px; }
        .kpis .hint { font-size: 9px; color: #8b979b; margin-top: 2px; }
        .badge { display: inline-block; font-size: 10px; font-weight: bold; padding: 2px 7px; border-radius: 3px; margin-top: 3px; }
        .badge-sip { background: #e2f3ec; color: #146c48; }
        .badge-bagus { background: #eaf3d9; color: #4d6b17; }
        .badge-cukup { background: #fdf1d7; color: #92650a; }
        .badge-kurang { background: #fbe6d8; color: #9a4816; }
        .badge-parah { background: #fbe2e2; color: #a1282a; }
        .badge-default { background: #e0e6e4; color: #566469; }
        .volume { width: 100%; margin-top: 8px; }
        .volume td { width: 25%; padding: 6px 8px; border: 1px solid #d8dedc; }
        .volume .label { font-size: 9px; text-transform: uppercase; color: #8b979b; display: block; }
        .volume .value { font-size: 13px; font-weight: bold; margin-top: 2px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $account->name }} — Growth Report</h1>
        <p class="meta">
            Generated {{ $generatedAt }}
            @if ($summary && $summary['platform'])
                &middot; {{ $summary['platform'] }}
            @endif
            @if ($summary && $summary['latest_cycle_label'])
                &middot; Latest cycle: {{ $summary['latest_cycle_label'] }}
            @endif
        </p>
    </div>

    @if ($summary)
        <p class="section-title">Overview</p>
        <table class="kpis">
            <tr>
                <td>
                    <span class="label">Followers</span>
                    <span class="value">{{ $summary['followers'] !== null ? number_format($summary['followers']) : '—' }}</span>
                    @if ($summary['followers_delta'] !== null)
                        <span class="{{ $summary['followers_delta'] >= 0 ? 'delta-up' : 'delta-down' }}">
                            {{ $summary['followers_delta'] >= 0 ? '+' : '' }}{{ round($summary['followers_delta'], 1) }}% vs last cycle
                        </span>
                    @endif
                </td>
                <td>
                    <span class="label">Health</span>
                    @if ($summary['health_label'])
                        <span class="badge badge-{{ strtolower($summary['health_label']) }}">{{ $summary['health_label'] }}</span>
                    @else
                        <span class="value">—</span>
                    @endif
                    <span class="hint">{{ $summary['health_rate'] !== null ? round($summary['health_rate'], 2) : '—' }} rate</span>
                </td>
                <td>
                    <span class="label">Growth Rate</span>
                    <span class="value">{{ $summary['growth_rate'] !== null ? round($summary['growth_rate'], 2) : '—' }}%</span>
                    <span class="hint">latest cycle</span>
                </td>
                <td>
                    <span class="label">Avg Views</span>
                    <span class="value">{{ $summary['avg_views'] !== null ? number_format($summary['avg_views']) : '—' }}</span>
                    <span class="hint">{{ $summary['total_posts'] !== null ? number_format($summary['total_posts']) : 0 }} posts total</span>
                </td>
                <td>
                    <span class="label">Median Views</span>
                    <span class="value">{{ ($summary['median_views'] ?? null) !== null ? number_format($summary['median_views']) : '—' }}</span>
                    <span class="hint">typical post</span>
                </td>
            </tr>
        </table>

        @if (! empty($summary['volume']))
            <p class="section-title">Volume — latest cycle vs. the one before it</p>
            <table class="volume">
                <tr>
                    @foreach ($summary['volume'] as $tile)
                        <td>
                            <span class="label">{{ $tile['label'] }}</span>
                            <span class="value">{{ $tile['value'] !== null ? number_format($tile['value']) : '—' }}</span>
                            @if ($tile['delta'] !== null)
                                <span class="{{ $tile['delta'] >= 0 ? 'delta-up' : 'delta-down' }}">
                                    {{ $tile['delta'] >= 0 ? '▲' : '▼' }} {{ abs(round($tile['delta'], 1)) }}%
                                </span>
                            @endif
                        </td>
                    @endforeach
                </tr>
            </table>
        @endif
    @endif

    @if ($aiSummary)
        <div class="ai-summary">
            <h2>AI Summary</h2>
            <p>{{ $aiSummary }}</p>
        </div>
    @endif

    <p class="section-title">Charts</p>
    @foreach ($charts as $chart)
        <div class="chart-block">
            <p style="font-weight: bold; margin: 0 0 4px;">{{ $chart['label'] }}</p>
            <img src="{{ $chart['image'] }}" alt="{{ $chart['label'] }}">
        </div>
    @endforeach
</body>
</html>
