<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #12181a; }
        h1 { font-size: 16px; margin: 0; }
        h2 { font-size: 13px; margin: 22px 0 8px; }
        .meta { color: #566469; font-size: 10px; margin-top: 4px; }
        .header { border-bottom: 2px solid #12181a; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 4px; }
        th { text-align: left; border-bottom: 1.5px solid #12181a; padding: 6px 4px; font-size: 10px; text-transform: uppercase; }
        td { padding: 5px 4px; border-bottom: 1px solid #d8dedc; }
        .num { text-align: right; }
        .rank { width: 34px; }
        .best td { font-weight: bold; }
        .empty { text-align: center; color: #8b979b; padding: 18px 0; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Brand Matrix — Best PM &amp; Best Conceptor</h1>
        <p class="meta">
            Generated {{ $generatedAt }} &middot; {{ $filterSummary }} &middot; Ranked by median views per post
        </p>
        <p class="meta">
            Grouped by: {{ $groupedBy }}
        </p>
    </div>

    <h2>Project Managers</h2>
    <table>
        <thead>
            <tr>
                <th class="rank">#</th>
                <th>Name</th>
                <th class="num">Median Views</th>
                <th class="num">Total Views</th>
                <th class="num">Min Views</th>
                <th class="num">Max Views</th>
                <th class="num">Total Reels</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($projectManagers as $i => $person)
                <tr @class(['best' => $i === 0])>
                    <td class="rank">{{ $i + 1 }}</td>
                    <td>{{ $person['employee_name'] }}</td>
                    <td class="num">{{ number_format($person['avg_views']) }}</td>
                    <td class="num">{{ number_format($person['total_views']) }}</td>
                    <td class="num">{{ number_format($person['min_views']) }}</td>
                    <td class="num">{{ number_format($person['max_views']) }}</td>
                    <td class="num">{{ number_format($person['post_count']) }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="empty">No project managers with recorded views for these filters.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Conceptors</h2>
    <table>
        <thead>
            <tr>
                <th class="rank">#</th>
                <th>Name</th>
                <th class="num">Median Views</th>
                <th class="num">Total Views</th>
                <th class="num">Min Views</th>
                <th class="num">Max Views</th>
                <th class="num">Total Reels</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($conceptors as $i => $person)
                <tr @class(['best' => $i === 0])>
                    <td class="rank">{{ $i + 1 }}</td>
                    <td>{{ $person['employee_name'] }}</td>
                    <td class="num">{{ number_format($person['avg_views']) }}</td>
                    <td class="num">{{ number_format($person['total_views']) }}</td>
                    <td class="num">{{ number_format($person['min_views']) }}</td>
                    <td class="num">{{ number_format($person['max_views']) }}</td>
                    <td class="num">{{ number_format($person['post_count']) }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="empty">No conceptors with recorded views for these filters.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
