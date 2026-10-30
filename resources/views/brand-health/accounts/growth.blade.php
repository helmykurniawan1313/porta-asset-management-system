@extends('layouts.app')

@section('title', $account->name . ' — Growth | Porta IT')
@section('header_title', 'Brand Health — ' . $account->name . ' Growth')

@section('content')
    <div class="mb-3">
        <a href="{{ route('brand-health.accounts.index') }}" class="btn btn-sm btn-light"><i class="bi bi-arrow-left"></i> Back to Accounts</a>
        <form method="POST" action="{{ route('brand-health.accounts.summarize', $account) }}" class="d-inline" id="summarize-form">
            @csrf
            <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-stars"></i> Generate AI Summary</button>
        </form>
    </div>

    @if($account->ai_summary)
        <div class="alert alert-light-primary color-primary mb-4">
            <strong>AI Summary</strong> ({{ $account->ai_summary_generated_at?->format('M j, Y g:i A') }})
            <p class="mb-0 mt-1">{{ $account->ai_summary }}</p>
        </div>
    @endif

    <ul class="nav nav-tabs mb-3" role="tablist">
        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-overview" type="button">Overview</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-volume" type="button">Volume</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-scores" type="button">Scores</button></li>
        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-er" type="button">Engagement Rate</button></li>
    </ul>

    <div class="tab-content">
        <div class="tab-pane fade show active" id="tab-overview">
            <div class="card shadow-sm border-0"><div class="card-body"><canvas id="chart-overview" height="90"></canvas></div></div>
        </div>
        <div class="tab-pane fade" id="tab-volume">
            <div class="card shadow-sm border-0"><div class="card-body"><canvas id="chart-volume" height="90"></canvas></div></div>
        </div>
        <div class="tab-pane fade" id="tab-scores">
            <div class="card shadow-sm border-0"><div class="card-body"><canvas id="chart-scores" height="90"></canvas></div></div>
        </div>
        <div class="tab-pane fade" id="tab-er">
            <div class="card shadow-sm border-0"><div class="card-body"><canvas id="chart-er" height="90"></canvas></div></div>
        </div>
    </div>
@endsection

@section('scripts')
<script src="{{ asset('assets/extensions/chart.js/chart.umd.js') }}"></script>
<script>
    const cycles = @json($cycles->values());
    const labels = cycles.map(c => c.label);

    new Chart(document.getElementById('chart-overview'), {
        type: 'line',
        data: { labels, datasets: [
            { label: 'End Followers', data: cycles.map(c => c.end_follower), borderColor: '#4361ee', tension: 0.3 },
        ]},
    });

    new Chart(document.getElementById('chart-volume'), {
        type: 'bar',
        data: { labels, datasets: [
            { label: 'Reach', data: cycles.map(c => c.reach), backgroundColor: '#4361ee' },
            { label: 'Views', data: cycles.map(c => c.views), backgroundColor: '#f72585' },
            { label: 'Engagement', data: cycles.map(c => c.engagement), backgroundColor: '#4cc9f0' },
        ]},
    });

    new Chart(document.getElementById('chart-scores'), {
        type: 'line',
        data: { labels, datasets: [
            { label: 'Visibility', data: cycles.map(c => c.visibility_rate), borderColor: '#f72585', tension: 0.3 },
            { label: 'Engagement', data: cycles.map(c => c.engagement_score), borderColor: '#4cc9f0', tension: 0.3 },
            { label: 'Health', data: cycles.map(c => c.health_rate), borderColor: '#4caf50', tension: 0.3 },
        ]},
        options: { scales: { y: { min: 0, max: 100 } } },
    });

    new Chart(document.getElementById('chart-er'), {
        type: 'line',
        data: { labels, datasets: [
            { label: 'ER of Reach (%)', data: cycles.map(c => c.er_reach_rate), borderColor: '#f72585', tension: 0.3 },
            { label: 'ER of Followers (%)', data: cycles.map(c => c.er_follower_rate), borderColor: '#4361ee', tension: 0.3 },
        ]},
    });

    document.getElementById('summarize-form').addEventListener('submit', function (e) {
        e.preventDefault();
        const btn = this.querySelector('button');
        btn.disabled = true;
        btn.innerHTML = 'Generating…';
        fetch(this.action, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, 'Accept': 'application/json' },
        }).then(r => r.json()).then(data => {
            if (data.summary) {
                location.reload();
            } else {
                alert(data.message || 'Failed to generate summary.');
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-stars"></i> Generate AI Summary';
            }
        });
    });
</script>
@endsection
