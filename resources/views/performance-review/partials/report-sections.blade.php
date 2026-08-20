@if($sections->isEmpty())
    <p class="text-muted text-center py-5">No submitted reviews yet for {{ $employee['name'] }}.</p>
@endif

@php
    $typeLabels = ['self' => 'Self Review', 'peer' => 'Peer Review', 'upward' => 'Upward Review', 'downward' => 'Downward Review'];
@endphp

@foreach($sections as $section)
    <div class="card border mb-4">
        <div class="card-body">
            <h6 class="fw-bold mb-1">{{ $typeLabels[$section['type']] ?? $section['type'] }}</h6>
            <p class="text-muted small mb-3">{{ $section['cycleName'] }} — {{ $section['templateName'] }} ({{ count($section['reviewers']) }} reviewer{{ count($section['reviewers']) === 1 ? '' : 's' }})</p>

            @if(count($section['ratingRows']) > 0)
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

            @foreach($section['textAnswers'] as $textQuestion)
                <p class="fw-bold small mb-1">{{ $textQuestion['question'] }}</p>
                @foreach($textQuestion['answers'] as $answer)
                    <div class="border rounded p-2 mb-2 bg-light">
                        <p class="text-muted small text-uppercase fw-bold mb-1">{{ $answer['reviewer'] }}</p>
                        <p class="mb-0">{{ $answer['answer'] }}</p>
                    </div>
                @endforeach
            @endforeach
        </div>
    </div>
@endforeach
