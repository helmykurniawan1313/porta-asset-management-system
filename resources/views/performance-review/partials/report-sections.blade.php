@if($sections->isEmpty())
    <p class="text-muted text-center py-5">No submitted reviews yet for {{ $employee['name'] }}.</p>
@endif

@php
    $typeLabels = ['self' => 'Self Review', 'peer' => 'Peer Review', 'upward' => 'Upward Review', 'downward' => 'Downward Review'];
@endphp

@php
    $sectionsByMonth = $sections
        ->groupBy(fn ($section) => $section['cycleStartsAt']?->format('Y-m') ?? 'unknown')
        ->sortKeysDesc();
@endphp

@foreach($sectionsByMonth as $monthKey => $monthSections)
    <h6 class="text-muted fw-bold text-uppercase small mb-3 mt-4">
        {{ $monthKey === 'unknown' ? 'Undated' : $monthSections->first()['cycleStartsAt']->format('F Y') }}
    </h6>

    @foreach($monthSections as $section)
        <div class="card border mb-4">
            <div class="card-body">
                <h6 class="fw-bold mb-1">{{ $typeLabels[$section['type']] ?? $section['type'] }}</h6>
                <p class="text-muted small mb-3">{{ $section['cycleName'] }} — {{ $section['templateName'] }} ({{ count($section['reviewers']) }} reviewer{{ count($section['reviewers']) === 1 ? '' : 's' }})</p>

                @if(count($section['ratingRows']) > 0)
                    @if($section['type'] === 'self')
                        <div class="table-responsive mb-2">
                            <table class="table table-sm table-bordered align-middle">
                                <thead>
                                    <tr>
                                        <th style="width: 30%;">Criteria</th>
                                        <th class="text-center">Answer</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($section['ratingRows'] as $row)
                                        <tr>
                                            <td>{{ $row['criteria'] }}</td>
                                            <td class="text-center">{{ $row['average'] ?? '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @php
                            $overallAverage = collect($section['ratingRows'])->pluck('average')->filter(fn ($v) => $v !== null)->average();
                        @endphp
                        @if($overallAverage !== null)
                            <p class="text-muted small mb-3">
                                Overall average: <span class="fw-bold text-body">{{ round($overallAverage, 1) }}</span>
                            </p>
                        @endif
                    @else
                        <div class="table-responsive mb-3">
                            <table class="table table-sm table-bordered align-middle">
                                <thead>
                                    <tr>
                                        <th style="width: 30%;">Criteria</th>
                                        @foreach($section['reviewers'] as $reviewer)
                                            <th class="text-center">{{ $reviewer['name'] }}</th>
                                        @endforeach
                                        <th class="text-center">Avg</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($section['ratingRows'] as $row)
                                        <tr>
                                            <td>{{ $row['criteria'] }}</td>
                                            @foreach($row['scores'] as $score)
                                                <td class="text-center">{{ $score ?? '—' }}</td>
                                            @endforeach
                                            <td class="text-center fw-bold">{{ $row['average'] ?? '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                @endif

                @foreach($section['textAnswers'] as $textQuestion)
                    <p class="fw-bold small mb-1">{{ $textQuestion['question'] }}</p>
                    @foreach($textQuestion['answers'] as $answer)
                        <div class="report-answer-box rounded p-2 mb-2">
                            <p class="text-muted small text-uppercase fw-bold mb-1">{{ $answer['reviewer'] }}</p>
                            <p class="mb-0">{{ $answer['answer'] }}</p>
                        </div>
                    @endforeach
                @endforeach
            </div>
        </div>
    @endforeach
@endforeach

<style>
    .report-answer-box {
        background: var(--bs-tertiary-bg, var(--bs-secondary-bg));
        border: 1px solid var(--bs-border-color);
    }
</style>
