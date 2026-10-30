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
        .ai-summary { margin-top: 16px; padding: 12px 14px; background: #f2f6f5; border-left: 3px solid #0f766e; }
        .ai-summary h2 { font-size: 11px; text-transform: uppercase; letter-spacing: 0.04em; color: #0f766e; margin: 0 0 6px; }
        .ai-summary p { margin: 0; line-height: 1.5; }
        .legend { margin-top: 14px; padding-top: 6px; font-size: 7.5px; color: #566469; border-top: 1px solid #d8dedc; text-align: center; }
        .legend strong { color: #12181a; }
        .summary-table { margin-top: 16px; width: auto; }
        .summary-table th, .summary-table td { padding: 5px 14px; text-align: center; }
        .summary-table td { font-size: 13px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Brand Matrix — Account Health by Cycles</h1>
        <p class="meta">
            Generated {{ $generatedAt }} &middot; Filters: {{ $filterSummary }} &middot;
            {{ count($cycles) }} {{ Str::plural('cycle', count($cycles)) }}
        </p>
    </div>

    @if ($aiSummary)
        <div class="ai-summary">
            <h2>AI Summary</h2>
            <p>{{ $aiSummary }}</p>
        </div>
    @endif

    <table class="summary-table">
        <thead>
            <tr>
                @foreach ($healthBreakdown as $row)
                    <th style="background-color: #{{ $row['color']['bg'] }}; color: #{{ $row['color']['ink'] }}">{{ $row['label'] }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            <tr>
                @foreach ($healthBreakdown as $row)
                    <td style="background-color: #{{ $row['color']['bg'] }}; color: #{{ $row['color']['ink'] }}">{{ $row['count'] }}</td>
                @endforeach
            </tr>
        </tbody>
    </table>

    <table>
        <thead>
            <tr>
                <th>Account</th>
                <th>Platform</th>
                <th>Period</th>
                <th>PM Name</th>
                <th class="num">Growth Rate</th>
                <th class="num">Visibility</th>
                <th class="num">Engagement</th>
                <th class="num">Health Rate</th>
                <th class="num">Health</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($cycles as $cycle)
                <tr>
                    <td>{{ $cycle['account']['name'] }}</td>
                    <td>{{ $cycle['platform'] === 'tiktok' ? 'TikTok' : 'Instagram' }}</td>
                    <td>{{ $cycle['cycle_start_date_formatted'] }} &ndash; {{ $cycle['cycle_end_date_formatted'] }}</td>
                    <td>{{ $cycle['project_manager']['name'] ?? '—' }}</td>
                    <td class="num">{{ $cycle['scores']['growth_rate'] }}%</td>
                    <td class="num">{{ $cycle['scores']['visibility_rate'] }}</td>
                    <td class="num">{{ $cycle['scores']['engagement_score'] }}</td>
                    <td class="num">{{ $cycle['scores']['health_rate'] }}</td>
                    @php($color = $healthColors[$cycle['scores']['health_label']] ?? null)
                    <td class="num" style="{{ $color ? "background-color: #{$color['bg']}; color: #{$color['ink']}" : '' }}">{{ $cycle['scores']['health_label'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="empty">No cycles match the current filters.</td>
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
