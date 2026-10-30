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
        .legend { margin-top: 14px; padding-top: 6px; font-size: 7.5px; color: #566469; border-top: 1px solid #d8dedc; text-align: center; }
        .legend strong { color: #12181a; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Brand Matrix — Account Performance</h1>
        <p class="meta">
            Generated {{ $generatedAt }} &middot; Filters: {{ $filterSummary }} &middot;
            {{ count($rows) }} {{ Str::plural('row', count($rows)) }}
        </p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Account</th>
                <th>Platform</th>
                <th>Latest Cycle</th>
                <th>PM Name</th>
                <th class="num">Growth Rate</th>
                <th class="num">Visibility</th>
                <th class="num">Engagement</th>
                <th class="num">Health Rate</th>
                <th class="num">Health</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row['account_name'] }}</td>
                    <td>{{ $row['platform'] === 'tiktok' ? 'TikTok' : 'Instagram' }}</td>
                    <td>{{ $row['latest_cycle_label'] }}</td>
                    <td>{{ $row['project_manager']['name'] ?? '—' }}</td>
                    <td class="num">{{ $row['growth_rate'] }}%</td>
                    <td class="num">{{ $row['visibility_rate'] }}</td>
                    <td class="num">{{ $row['engagement_score'] }}</td>
                    <td class="num">{{ $row['health_rate'] }}</td>
                    <td class="num">{{ $row['health_label'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="empty">No accounts match the current filters.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="legend">
        <strong>Growth Rate</strong> : Presentase pertumbuhan followers selama periode analisis &nbsp;|&nbsp;
        <strong>Visibility</strong> : Nilai yang menggambarkan peforma distribusi konten &nbsp;|&nbsp;
        <strong>Engagement</strong> : Nilai yang menggambarkan kualitas interaksi audiens
    </div>
</body>
</html>
