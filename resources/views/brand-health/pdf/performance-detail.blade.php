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
        .aggregates { width: 100%; }
        .aggregates td { width: 25%; padding: 8px; border: 1px solid #d8dedc; vertical-align: top; }
        .aggregates .name { font-weight: bold; font-size: 12px; }
        .aggregates .badge { font-size: 9px; font-weight: bold; color: #0f6e63; }
        .aggregates .big { font-size: 18px; font-weight: bold; margin-top: 4px; }
        .insights td { padding: 6px 8px; border: 1px solid #d8dedc; }
        .insights .label { font-size: 9px; text-transform: uppercase; color: #8b979b; display: block; }
        .insights .value { font-size: 13px; font-weight: bold; }
        .summary-box { border: 1px solid #d8dedc; padding: 10px; margin-top: 6px; line-height: 1.5; }
        .summary-meta { font-size: 9px; color: #8b979b; margin-top: 6px; }
        .notes { margin-top: 4px; }
        .note-tag { display: inline-block; background: #e3f1ee; color: #0f6e63; font-size: 9px; font-weight: bold; padding: 3px 8px; border-radius: 3px; margin: 0 4px 4px 0; }
        .links td { padding: 6px 8px; border: 1px solid #d8dedc; font-size: 10px; word-break: break-all; }
        .proof-img { max-width: 260px; max-height: 260px; margin-top: 6px; border: 1px solid #d8dedc; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $performance->account->name }}</h1>
        <p class="meta">
            Posted {{ $performance->post_date->format('M j, Y') }}
            @if ($performance->platform)
                &middot; {{ ucfirst($performance->platform) }}
            @endif
        </p>
    </div>

    <table class="inputs">
        <tr>
            <td><span class="label">Preview Date</span><span class="value">{{ $performance->preview_date?->format('M j, Y') ?? '—' }}</span></td>
            <td><span class="label">Cycle</span><span class="value">{{ $performance->cycle ? $performance->cycle->cycle_start_date->format('M j, Y').' – '.$performance->cycle->cycle_end_date->format('M j, Y') : 'No cycle' }}</span></td>
            <td><span class="label">Ads</span><span class="value">{{ $performance->ads === null ? '-' : ($performance->ads ? 'Yes' : 'No') }}</span></td>
            <td><span class="label">Followers</span><span class="value">{{ $performance->followers !== null ? number_format($performance->followers) : '—' }}</span></td>
            <td><span class="label">Views H+7</span><span class="value">{{ $performance->total_views_h7 !== null ? number_format($performance->total_views_h7) : '—' }}</span></td>
        </tr>
    </table>

    @if ($performance->notes)
        <p class="section-title">Notes</p>
        <div class="notes">
            @foreach ($performance->notes as $note)
                <span class="note-tag">{{ $note }}</span>
            @endforeach
        </div>
    @endif

    <p class="section-title">Status</p>
    <table class="aggregates">
        <tr>
            <td>
                <div class="name">Account Category <span class="badge">{{ $performance->follower_category ?? '—' }}</span></div>
            </td>
            <td>
                <div class="name">Views H+7 Status <span class="badge">{{ $performance->views_status ?? '—' }}</span></div>
            </td>
            <td>
                <div class="name">Project Manager</div>
                <div class="big" style="font-size: 13px">{{ $performance->project_manager?->name ?? '—' }}</div>
            </td>
            <td>
                <div class="name">Conceptor</div>
                <div class="big" style="font-size: 13px">{{ $performance->conceptor?->name ?? '—' }}</div>
            </td>
        </tr>
    </table>

    @if ($snapshot = $performance->igSnapshots->first())
        <p class="section-title">Instagram Insights — as of {{ $snapshot->fetched_at->format('M j, Y g:i A') }}</p>
        <table class="insights">
            <tr>
                @foreach (['reach' => 'Reach', 'likes' => 'Likes', 'comments' => 'Comments', 'shares' => 'Shares'] as $key => $label)
                    @if ($snapshot->{$key} !== null)
                        <td><span class="label">{{ $label }}</span><span class="value">{{ number_format($snapshot->{$key}) }}</span></td>
                    @endif
                @endforeach
            </tr>
            <tr>
                @foreach (['saved' => 'Saved', 'total_interactions' => 'Total Interactions', 'views' => 'Views', 'ig_reels_avg_watch_time' => 'Avg Watch Time'] as $key => $label)
                    @if ($snapshot->{$key} !== null)
                        <td><span class="label">{{ $label }}</span><span class="value">{{ number_format($snapshot->{$key}) }}</span></td>
                    @endif
                @endforeach
            </tr>
        </table>
    @endif

    @if ($performance->videoLinks->isNotEmpty())
        <p class="section-title">Video Links</p>
        <table class="links">
            @foreach ($performance->videoLinks as $link)
                <tr>
                    <td>{{ ucfirst($link->platform ?? '—') }}: {{ $link->url }}</td>
                </tr>
            @endforeach
        </table>
    @endif

    @if ($performance->proof_path && file_exists(storage_path('app/public/'.$performance->proof_path)))
        <p class="section-title">Upload Proof</p>
        <img class="proof-img" src="{{ storage_path('app/public/'.$performance->proof_path) }}">
    @endif

    @if ($performance->ai_summary)
        <p class="section-title">AI Summary</p>
        <div class="summary-box">
            {{ $performance->ai_summary }}
            @if ($performance->ai_summary_generated_at)
                <div class="summary-meta">Generated {{ $performance->ai_summary_generated_at->format('M j, Y g:i A') }}</div>
            @endif
        </div>
    @endif
</body>
</html>
