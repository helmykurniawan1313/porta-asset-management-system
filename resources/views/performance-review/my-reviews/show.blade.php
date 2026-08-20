@extends('layouts.app')

@section('title', 'Fill Out Review | Porta IT')
@section('header_title', 'Review — ' . $assignment->reviewee->name)

@section('content')
    <div class="mb-3">
        <a href="{{ route('my-reviews.index') }}" class="btn btn-sm btn-light-secondary">
            <i class="bi bi-arrow-left"></i> Back to My Reviews
        </a>
    </div>

    @if($errors->any())
        <div class="alert alert-light-danger color-danger d-flex align-items-center shadow-sm mb-4">
            <i class="bi bi-exclamation-triangle me-2"></i> {{ $errors->first() }}
        </div>
    @endif

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body py-4">
            <div class="row">
                <div class="col-md-6">
                    <p class="text-muted small fw-bold text-uppercase mb-1">Reviewing</p>
                    <p class="mb-0 fw-bold fs-5">
                        {{ $assignment->reviewee->id === $assignment->reviewer_id ? 'Yourself' : $assignment->reviewee->name }}
                    </p>
                </div>
                <div class="col-md-6">
                    <p class="text-muted small fw-bold text-uppercase mb-1">Cycle / Type</p>
                    <p class="mb-0">{{ $assignment->reviewCycle->name }} &middot; <span class="text-capitalize">{{ $assignment->review_type }}</span></p>
                </div>
            </div>
        </div>
    </div>

    @if($assignment->status === 'submitted')
        <div class="alert alert-light-success color-success mb-4">
            <i class="bi bi-check-circle me-2"></i> This review has already been submitted and can no longer be edited.
        </div>
    @endif

    @if(!$template)
        <div class="card shadow-sm border-0">
            <div class="card-body py-5 text-center text-muted">
                No review template is configured for this cycle yet. Please check back later.
            </div>
        </div>
    @else
        <form action="{{ route('my-reviews.store', $assignment->id) }}" method="POST">
            @csrf
            <div class="card shadow-sm border-0">
                <div class="card-body py-4">
                    @foreach($template->questions->sortBy('order') as $question)
                        @php $existing = $existingResponses->firstWhere('question_id', $question->id); @endphp
                        <div class="mb-4 pb-4 border-bottom">
                            <label class="form-label fw-bold">{{ $question->text }}</label>
                            <input type="hidden" name="responses[{{ $loop->index }}][question_id]" value="{{ $question->id }}">

                            @if($question->type === 'rating_scale')
                                <div class="d-flex gap-2">
                                    @for($i = 1; $i <= 6; $i++)
                                        <label class="btn btn-outline-primary btn-sm rounded-pill px-3 {{ optional($existing)->answer_rating == $i ? 'active' : '' }}">
                                            <input type="radio" name="responses[{{ $loop->parent->index }}][answer_rating]" value="{{ $i }}" class="d-none"
                                                {{ optional($existing)->answer_rating == $i ? 'checked' : '' }}
                                                {{ $assignment->status === 'submitted' ? 'disabled' : '' }}>
                                            {{ $i }}
                                        </label>
                                    @endfor
                                </div>
                            @elseif($question->type === 'multiple_choice')
                                <select name="responses[{{ $loop->index }}][answer_text]" class="form-select" {{ $assignment->status === 'submitted' ? 'disabled' : '' }}>
                                    <option value="">— Select —</option>
                                    @foreach($question->options ?? [] as $option)
                                        <option value="{{ $option }}" @selected(optional($existing)->answer_text === $option)>{{ $option }}</option>
                                    @endforeach
                                </select>
                            @else
                                <textarea name="responses[{{ $loop->index }}][answer_text]" class="form-control" rows="3"
                                    {{ $assignment->status === 'submitted' ? 'disabled' : '' }}>{{ optional($existing)->answer_text }}</textarea>
                            @endif
                        </div>
                    @endforeach
                </div>

                @if($assignment->status !== 'submitted')
                <div class="card-footer bg-light border-0 d-flex justify-content-end gap-2 py-3">
                    <button type="submit" name="submit" value="0" class="btn btn-light-secondary px-4 fw-bold">
                        SAVE DRAFT
                    </button>
                    <button type="submit" name="submit" value="1" class="btn btn-primary px-4 fw-bold" onclick="return confirm('Submit this review? You will not be able to edit it afterwards.')">
                        SUBMIT REVIEW
                    </button>
                </div>
                @endif
            </div>
        </form>
    @endif
@endsection
