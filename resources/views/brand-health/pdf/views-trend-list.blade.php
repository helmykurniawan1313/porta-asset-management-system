<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #12181a; }
        h1 { font-size: 16px; margin: 0; }
        .meta { color: #566469; font-size: 10px; margin-top: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th { text-align: left; border-bottom: 1.5px solid #12181a; padding: 6px 4px; font-size: 10px; text-transform: uppercase; }
        td { padding: 5px 4px; border-bottom: 1px solid #d8dedc; }
        .num { text-align: right; }
        .header { border-bottom: 2px solid #12181a; padding-bottom: 10px; }
        .empty { text-align: center; color: #8b979b; padding: 24px 0; }
        .trend-setback { color: #a1282a; font-weight: bold; }
        .trend-stagnant { color: #9a4816; font-weight: bold; }
        .trend-growing { color: #146c48; font-weight: bold; }
        .trend-stable { color: #4d6b17; font-weight: bold; }
        .trend-insufficient_data { color: #8b979b; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Brand Matrix — Views Trend</h1>
        <p class="meta">
            Generated {{ $generatedAt }} &middot; Filters: {{ $filterSummary }} &middot;
            {{ count($rows) }} {{ Str::plural('account', count($rows)) }}
        </p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Account</th>
                <th>Platform</th>
                <th>Trend</th>
                <th class="num">Last Cycle Median</th>
                <th class="num">Prior Cycle Median</th>
                <th class="num">Max</th>
                <th class="num">Min</th>
                <th class="num">Total Reels</th>
                <th class="num">Delta %</th>
                <th>As Of</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row['account_name'] }}</td>
                    <td>{{ $row['platform'] === 'tiktok' ? 'TikTok' : 'Instagram' }}</td>
                    <td class="trend-{{ $row['trend'] }}">{{ ucfirst(str_replace('_', ' ', $row['trend'])) }}</td>
                    <td class="num">{{ $row['last_avg_views'] !== null ? number_format($row['last_avg_views']) : '—' }}</td>
                    <td class="num">{{ $row['prior_avg_views'] !== null ? number_format($row['prior_avg_views']) : '—' }}</td>
                    <td class="num">{{ $row['last_max_views'] !== null ? number_format($row['last_max_views']) : '—' }}</td>
                    <td class="num">{{ $row['last_min_views'] !== null ? number_format($row['last_min_views']) : '—' }}</td>
                    <td class="num">{{ number_format($row['last_post_count'] ?? 0) }}</td>
                    <td class="num">{{ $row['delta_pct'] !== null ? ($row['delta_pct'] > 0 ? '+' : '').$row['delta_pct'].'%' : '—' }}</td>
                    <td>{{ $row['last_cycle_label'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="empty">No accounts match the current filters.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
