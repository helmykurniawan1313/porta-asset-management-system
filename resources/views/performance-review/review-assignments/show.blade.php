@extends('layouts.app')

@section('title', 'Assignment Detail | Porta IT')
@section('header_title', 'Assignment Detail')

@section('content')
    <a href="{{ route('hr.review-cycles.assignments.index', $reviewCycle) }}" class="btn btn-sm btn-light-secondary mb-3">
        <i class="bi bi-arrow-left"></i> Back to Assignments
    </a>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body py-4">
            <div class="row mb-3">
                <div class="col-md-6">
                    <p class="text-muted small mb-1 text-uppercase fw-bold">Reviewer</p>
                    <p class="fw-bold">{{ $assignment->reviewer->name }} ({{ $assignment->reviewer->email }})</p>
                </div>
                <div class="col-md-6">
                    <p class="text-muted small mb-1 text-uppercase fw-bold">Reviewee</p>
                    <p class="fw-bold">{{ $assignment->reviewee->name }} ({{ $assignment->reviewee->email }})</p>
                </div>
            </div>
            <div class="row mb-4">
                <div class="col-md-6">
                    <p class="text-muted small mb-1 text-uppercase fw-bold">Type</p>
                    <span class="badge bg-light-info text-info text-capitalize">{{ $assignment->review_type }}</span>
                </div>
                <div class="col-md-6">
                    <p class="text-muted small mb-1 text-uppercase fw-bold">Status</p>
                    <span class="badge bg-light-secondary text-secondary text-capitalize">{{ str_replace('_', ' ', $assignment->status) }}</span>
                </div>
            </div>

            @if($template)
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Question</th>
                            <th>Answer</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($template->questions as $question)
                            @php
                                $response = $responses->firstWhere('question_id', $question->id);
                            @endphp
                            <tr>
                                <td>{{ $question->text }}</td>
                                <td>
                                    @if($question->type === 'rating_scale')
                                        {{ $response->answer_rating ?? '—' }}
                                    @else
                                        {{ $response->answer_text ?? '—' }}
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p class="text-muted">No template found for this cycle.</p>
            @endif
        </div>
    </div>
@endsection
