<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #12181a; }
        h1 { font-size: 16px; margin: 0; }
        .meta { color: #566469; font-size: 10px; margin-top: 4px; }
        .header { border-bottom: 2px solid #12181a; padding-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th { text-align: left; border-bottom: 1.5px solid #12181a; padding: 6px 4px; font-size: 10px; text-transform: uppercase; }
        td { padding: 5px 4px; border-bottom: 1px solid #d8dedc; }
        .num { text-align: right; }
        .rank { width: 34px; }
        .best td { font-weight: bold; }
        .empty { text-align: center; color: #8b979b; padding: 24px 0; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Brand Matrix — Ads Nominal Ranking</h1>
        <p class="meta">
            Generated {{ $generatedAt }} &middot; Filters: {{ $filterSummary }} &middot;
            {{ count($rows) }} {{ Str::plural('account', count($rows)) }}
        </p>
        <p class="meta">Spend is grouped by currency — amounts in different currencies are never added together.</p>
    </div>

    @foreach ($byCurrency as $currency => $currencyRows)
        <h2 style="font-size: 13px; margin: 20px 0 4px;">{{ $currency }}</h2>
        <table>
            <thead>
                <tr>
                    <th class="rank">#</th>
                    <th>Account</th>
                    <th class="num">Reach/Views Ads Spend</th>
                    <th class="num">Engagement Ads Spend</th>
                    <th class="num">Total Ads Spend</th>
                    <th class="num">Cycles</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($currencyRows as $i => $row)
                    <tr @class(['best' => $i === 0])>
                        <td class="rank">{{ $i + 1 }}</td>
                        <td>{{ $row['account_name'] }}</td>
                        <td class="num">{{ number_format($row['reach_views_ads_spend'], 2) }}</td>
                        <td class="num">{{ number_format($row['engagement_ads_spend'], 2) }}</td>
                        <td class="num">{{ number_format($row['total_ads_spend'], 2) }}</td>
                        <td class="num">{{ $row['cycle_count'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endforeach

    @if (count($rows) === 0)
        <table>
            <tbody>
                <tr><td class="empty">No accounts with ads spend recorded for this range.</td></tr>
            </tbody>
        </table>
    @endif
</body>
</html>
