@php
    $currencySymbols = ['IDR' => 'Rp', 'USD' => '$', 'EUR' => '€'];

    $formatAdsLine = function (bool $used, ?float $spend, ?string $currency) use ($currencySymbols) {
        if (! $used) {
            return 'Ads: No';
        }

        $symbol = $currencySymbols[$currency] ?? ($currency ?? 'IDR');

        return 'Ads: Yes — '.$symbol.' '.number_format($spend ?? 0, 2);
    };

    $totalAdsSpend = ($cycle->reach_views_ads_used ? (float) $cycle->reach_views_ads_spend : 0)
        + ($cycle->engagement_ads_used ? (float) $cycle->engagement_ads_spend : 0);

    $totalAdsSpendFormatted = ($currencySymbols[$cycle->ads_currency] ?? ($cycle->ads_currency ?? 'IDR')).' '.number_format($totalAdsSpend, 2);
@endphp
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
        table { width: 100%; border-collapse: collapse; }
        .inputs td { padding: 6px 8px; border: 1px solid #d8dedc; }
        .inputs .label { font-size: 9px; text-transform: uppercase; color: #8b979b; display: block; }
        .inputs .value { font-size: 13px; font-weight: bold; }
        .rates td { padding: 6px 4px; border-bottom: 1px solid #d8dedc; }
        .rates .formula { font-size: 9px; color: #8b979b; }
        .rates .ads { font-size: 9px; color: #0f6e63; font-weight: bold; display: block; }
        .rates .num { text-align: right; }
        .rates .score { text-align: right; font-weight: bold; color: #0f6e63; }
        .aggregates { width: 100%; }
        .aggregates td { width: 25%; padding: 8px; border: 1px solid #d8dedc; vertical-align: top; }
        .aggregates .name { font-weight: bold; font-size: 12px; }
        .aggregates .badge { font-size: 9px; font-weight: bold; color: #0f6e63; }
        .aggregates .big { font-size: 18px; font-weight: bold; margin-top: 4px; }
        .aggregates .formula { font-size: 9px; color: #8b979b; margin-top: 4px; }
        .summary-box { border: 1px solid #d8dedc; padding: 10px; margin-top: 6px; line-height: 1.5; }
        .summary-meta { font-size: 9px; color: #8b979b; margin-top: 6px; }
        .legend { margin-top: 18px; padding-top: 8px; font-size: 7.5px; color: #566469; border-top: 1px solid #d8dedc; text-align: center; }
        .legend strong { color: #12181a; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $cycle->account->name }}</h1>
        <p class="meta">
            {{ $cycle->cycle_start_date->format('M j, Y') }} &ndash; {{ $cycle->cycle_end_date->format('M j, Y') }}
            &nbsp;|&nbsp; {{ $cycle->platform === 'tiktok' ? 'TikTok' : 'Instagram' }}
            @if ($cycle->projectManager)
                &nbsp;|&nbsp; PM: {{ $cycle->projectManager->name }}
            @endif
        </p>
    </div>

    @php $isTiktok = $cycle->platform === 'tiktok'; @endphp

    <table class="inputs">
        <tr>
            <td><span class="label">Start Followers</span><span class="value">{{ number_format($cycle->start_follower) }}</span></td>
            <td><span class="label">End Followers</span><span class="value">{{ number_format($cycle->end_follower) }}</span></td>
            <td><span class="label">{{ $isTiktok ? 'Profile Views' : 'Reach' }}</span><span class="value">{{ number_format($cycle->reach) }}</span></td>
            <td><span class="label">Views</span><span class="value">{{ number_format($cycle->views) }}</span></td>
            <td><span class="label">Engagement</span><span class="value">{{ number_format($cycle->engagement) }}</span></td>
        </tr>
    </table>

    <p class="section-title">Rate Metrics</p>
    <table class="rates">
        <tr>
            <td>Growth<br><span class="formula">end &minus; start</span></td>
            <td class="num">{{ $scores['growth'] > 0 ? '+' : '' }}{{ $scores['growth'] }}</td>
            <td class="score">{{ $scores['growth_score'] }}</td>
        </tr>
        <tr>
            <td>Growth Rate<br><span class="formula">(end &minus; start) / start &times; 100</span></td>
            <td class="num">{{ $scores['growth_rate'] }}%</td>
            <td class="score">{{ $scores['growth_score'] }}</td>
        </tr>
        @if ($isTiktok)
        <tr>
            <td>Profile Rate<br><span class="formula">profile views / views &times; 1,000</span></td>
            <td class="num">{{ round($scores['profile_rate'], 2) }}</td>
            <td class="score">{{ $scores['profile_score'] }}</td>
        </tr>
        <tr>
            <td>View Rate<br><span class="formula">views / end followers</span></td>
            <td class="num">{{ round($scores['view_rate'] / 100, 2) }}</td>
            <td class="score">{{ $scores['view_score'] }}</td>
        </tr>
        <tr>
            <td>ER (of Views)<br><span class="ads">{{ $formatAdsLine((bool) $cycle->engagement_ads_used, $cycle->engagement_ads_spend, $cycle->ads_currency) }}</span><span class="formula">engagement / views &times; 100</span></td>
            <td class="num">{{ $scores['er_view_rate'] }}%</td>
            <td class="score">{{ $scores['er_view_score'] }}</td>
        </tr>
        @else
        <tr>
            <td>Reach Rate<br><span class="ads">{{ $formatAdsLine((bool) $cycle->reach_views_ads_used, $cycle->reach_views_ads_spend, $cycle->ads_currency) }}</span><span class="formula">reach / end followers</span></td>
            <td class="num">{{ round($scores['reach_rate'] / 100, 2) }}</td>
            <td class="score">{{ $scores['reach_score'] }}</td>
        </tr>
        <tr>
            <td>View Rate<br><span class="ads">{{ $formatAdsLine((bool) $cycle->reach_views_ads_used, $cycle->reach_views_ads_spend, $cycle->ads_currency) }}</span><span class="formula">views / end followers</span></td>
            <td class="num">{{ round($scores['view_rate'] / 100, 2) }}</td>
            <td class="score">{{ $scores['view_score'] }}</td>
        </tr>
        <tr>
            <td>ER (of Reach)<br><span class="ads">{{ $formatAdsLine((bool) $cycle->engagement_ads_used, $cycle->engagement_ads_spend, $cycle->ads_currency) }}</span><span class="formula">engagement / reach &times; 100</span></td>
            <td class="num">{{ $scores['er_reach_rate'] }}%</td>
            <td class="score">{{ $scores['er_reach_score'] }}</td>
        </tr>
        <tr>
            <td>ER (of Followers)<br><span class="ads">{{ $formatAdsLine((bool) $cycle->engagement_ads_used, $cycle->engagement_ads_spend, $cycle->ads_currency) }}</span><span class="formula">engagement / end followers &times; 100</span></td>
            <td class="num">{{ $scores['er_follower_rate'] }}%</td>
            <td class="score">{{ $scores['er_follower_score'] }}</td>
        </tr>
        @endif
        <tr>
            <td>Story Performance<br><span class="formula">manual input</span></td>
            <td class="num">{{ number_format($cycle->story_performance ?? 0) }}</td>
            <td class="score">-</td>
        </tr>
        <tr>
            <td>Total Ads Spend<br><span class="formula">reach &amp; views + engagement ads spend</span></td>
            <td class="num">{{ $totalAdsSpendFormatted }}</td>
            <td class="score">-</td>
        </tr>
    </table>

    <p class="section-title">Aggregate Scores</p>
    <table class="aggregates">
        <tr>
            <td>
                <div class="name">Growth Rate <span class="badge">{{ $scores['growth_label'] }}</span></div>
                <div class="big">{{ round($scores['growth_score'], 2) }}</div>
                <div class="formula">score based on growth rate</div>
            </td>
            <td>
                <div class="name">Visibility <span class="badge">{{ $scores['visibility_label'] }}</span></div>
                <div class="big">{{ round($scores['visibility_rate'], 2) }}</div>
                <div class="formula">{{ $isTiktok ? 'weighted average of Profile Score + View Score' : 'weighted average of Reach Score + View Score' }}</div>
            </td>
            <td>
                <div class="name">Engagement <span class="badge">{{ $scores['engagement_label'] }}</span></div>
                <div class="big">{{ round($scores['engagement_score'], 2) }}</div>
                <div class="formula">{{ $isTiktok ? 'ER-View Score' : 'weighted average of ER-Reach Score + ER-Follower Score' }}</div>
            </td>
            <td>
                <div class="name">Health <span class="badge">{{ $scores['health_label'] }}</span></div>
                <div class="big">{{ round($scores['health_rate'], 2) }}</div>
                <div class="formula">weighted average of Growth + Visibility + Engagement</div>
            </td>
        </tr>
    </table>

    @if ($cycle->ai_summary)
        <p class="section-title">AI Summary</p>
        <div class="summary-box">
            {{ $cycle->ai_summary }}
            @if ($cycle->ai_summary_generated_at)
                <div class="summary-meta">Generated {{ $cycle->ai_summary_generated_at->format('M j, Y g:i A') }}</div>
            @endif
        </div>
    @endif

    <div class="legend">
        <strong>Growth Rate</strong> : Presentase pertumbuhan followers selama periode analisis &nbsp;|&nbsp;
        <strong>Visibility</strong> : Nilai yang menggambarkan peforma distribusi konten &nbsp;|&nbsp;
        <strong>Engagement</strong> : Nilai yang menggambarkan kualitas interaksi audiens
    </div>
</body>
</html>
