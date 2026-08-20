@extends('layouts.app')

@section('title', 'My Review Report | Porta IT')
@section('header_title', 'My Review Report')

@section('content')
    <div class="alert alert-light-info color-info mb-4">
        <i class="bi bi-shield-lock me-2"></i>
        Reviewer identities are anonymized below (peer, upward and downward feedback) so you cannot tell who gave which answer.
    </div>

    @php
        $typeLabels = ['self' => 'Self Review', 'peer' => 'Peer Review', 'upward' => 'Upward Review', 'downward' => 'Downward Review'];
    @endphp

    @if($sections->isEmpty())
        <div class="card shadow-sm border-0">
            <div class="card-body py-5 text-center text-muted">
                No submitted reviews yet.
            </div>
        </div>
    @endif

    @foreach($sections as $section)
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-transparent border-0 pt-4">
                <h5 class="card-title mb-1">{{ $typeLabels[$section['type']] ?? $section['type'] }}</h5>
                <p class="text-muted small mb-0">
                    {{ $section['templateName'] }} &middot; {{ $section['cycleName'] }} &middot;
                    {{ count($section['reviewers']) }} reviewer{{ count($section['reviewers']) === 1 ? '' : 's' }}
                </p>
            </div>
            <div class="card-body py-4">
                @if(count($section['ratingRows']) > 0)
                <div class="table-responsive mb-4">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Criteria</th>
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
                    <p class="fw-bold mb-2">{{ $textQuestion['question'] }}</p>
                    @foreach($textQuestion['answers'] as $answer)
                        <div class="bg-light border rounded p-3 mb-2">
                            <p class="text-muted small fw-bold text-uppercase mb-1">{{ $answer['reviewer'] }}</p>
                            <p class="mb-0">{{ $answer['answer'] }}</p>
                        </div>
                    @endforeach
                @endforeach
            </div>
        </div>
    @endforeach
@endsection
