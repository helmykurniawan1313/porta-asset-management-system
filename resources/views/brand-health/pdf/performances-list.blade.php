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
    </style>
</head>
<body>
    <div class="header">
        <h1>Brand Matrix — Performance</h1>
        <p class="meta">
            Generated {{ $generatedAt }} &middot; Filters: {{ $filterSummary }} &middot;
            {{ count($performances) }} {{ Str::plural('record', count($performances)) }}
        </p>
    </div>

    @if ($aiSummary)
        <div class="ai-summary">
            <h2>AI Summary</h2>
            <p>{{ $aiSummary }}</p>
        </div>
    @endif

    <table>
        <thead>
            <tr>
                <th>Account</th>
                <th>Platform</th>
                <th>Post Date</th>
                <th>Ads</th>
                <th>PM</th>
                <th>Conceptor</th>
                <th class="num">Views H+7</th>
                <th class="num">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($performances as $performance)
                <tr>
                    <td>{{ $performance['account']['name'] }}</td>
                    <td>{{ ($performance['platform'] ?? 'instagram') === 'tiktok' ? 'TikTok' : 'Instagram' }}</td>
                    <td>{{ $performance['post_date_formatted'] }}</td>
                    <td>{{ $performance['ads'] === null ? '-' : ($performance['ads'] ? 'Yes' : 'No') }}</td>
                    <td>{{ $performance['project_manager']['name'] ?? '—' }}</td>
                    <td>{{ $performance['conceptor']['name'] ?? '—' }}</td>
                    <td class="num">{{ $performance['total_views_h7'] !== null ? number_format($performance['total_views_h7']) : '—' }}</td>
                    <td class="num">{{ $performance['views_status'] ?? '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="empty">No records match the current filters.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
