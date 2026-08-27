@extends('layouts.app')

@section('title', 'Fill Out Review | Porta IT')
@section('header_title', ucfirst($assignment->review_type) . ' — review for ' . $assignment->reviewee->name)

@section('content')
    @if($errors->any())
        <div class="alert alert-light-danger color-danger d-flex align-items-center shadow-sm mb-4">
            <i class="bi bi-exclamation-triangle me-2"></i> {{ $errors->first() }}
        </div>
    @endif

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
    @elseif($assignment->status === 'submitted')
        @php $questions = $template->questions->sortBy('order')->values(); @endphp
        <div class="review-answers-wrap mx-auto">
            <div class="row g-3">
                @foreach($questions as $question)
                    @php $existing = $existingResponses->firstWhere('question_id', $question->id); @endphp
                    <div class="col-md-6">
                        <div class="card shadow-sm border-0 review-question-card h-100">
                            <div class="card-body py-4 px-4">
                                <span class="badge-q mb-2">Q{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</span>
                                <p class="question-text mb-3">{{ $question->text }}</p>

                                @if($question->type === 'rating_scale')
                                    <div class="star-rating d-flex flex-row-reverse justify-content-start gap-1">
                                        @for($i = 6; $i >= 1; $i--)
                                            <input type="radio" id="star-view-{{ $question->id }}-{{ $i }}"
                                                name="view-responses[{{ $question->id }}]" value="{{ $i }}"
                                                {{ optional($existing)->answer_rating == $i ? 'checked' : '' }} disabled>
                                            <label for="star-view-{{ $question->id }}-{{ $i }}" class="star-label" title="{{ $i }}">
                                                <i class="bi bi-star-fill"></i>
                                            </label>
                                        @endfor
                                    </div>
                                @else
                                    <p class="mb-0 text-body">{{ optional($existing)->answer_text ?: '—' }}</p>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="text-center mt-4">
                <a href="{{ route('my-reviews.index') }}" class="text-muted small text-decoration-none">← Back to My Reviews</a>
            </div>
        </div>
    @else
        @php $questions = $template->questions->sortBy('order')->values(); @endphp
        <form id="review-wizard-form" action="{{ route('my-reviews.store', $assignment->id) }}" method="POST">
            @csrf

            <div class="review-wizard-wrap mx-auto">
                {{-- Top meta row --}}
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="badge-q" id="wizard-qbadge">Q01</span>
                    <span class="small text-muted"><span id="wizard-current">1</span> of {{ $questions->count() }}</span>
                </div>

                <div class="progress mb-4" style="height: 4px;">
                    <div id="wizard-progress-bar" class="progress-bar bg-primary" style="width: {{ $questions->count() ? round(100 / $questions->count()) : 0 }}%"></div>
                </div>

                <div class="card shadow-sm border-0 review-question-card">
                    <div class="card-body py-4 px-4">
                        @foreach($questions as $question)
                            @php $existing = $existingResponses->firstWhere('question_id', $question->id); @endphp
                            <div class="wizard-step text-center" data-step="{{ $loop->index }}" data-required="1" @if(!$loop->first) style="display:none" @endif>
                                <span class="status-pill mb-3 {{ optional($existing)->answer_rating || optional($existing)->answer_text ? 'answered' : '' }}">
                                    {{ optional($existing)->answer_rating || optional($existing)->answer_text ? 'Answered' : 'Unanswered' }}
                                </span>

                                <p class="question-text mx-auto mb-4">{{ $question->text }}</p>

                                <input type="hidden" name="responses[{{ $loop->index }}][question_id]" value="{{ $question->id }}">

                                @if($question->type === 'rating_scale')
                                    <div class="star-rating d-flex flex-row-reverse justify-content-center gap-1 mb-2">
                                        @for($i = 6; $i >= 1; $i--)
                                            <input type="radio" id="star-{{ $question->id }}-{{ $i }}"
                                                name="responses[{{ $loop->index }}][answer_rating]" value="{{ $i }}"
                                                {{ optional($existing)->answer_rating == $i ? 'checked' : '' }}>
                                            <label for="star-{{ $question->id }}-{{ $i }}" class="star-label" title="{{ $i }}">
                                                <i class="bi bi-star-fill"></i>
                                            </label>
                                        @endfor
                                    </div>
                                @elseif($question->type === 'multiple_choice')
                                    <select name="responses[{{ $loop->index }}][answer_text]" class="form-select form-select-lg mx-auto" style="max-width: 420px;">
                                        <option value="">— Select —</option>
                                        @foreach($question->options ?? [] as $option)
                                            <option value="{{ $option }}" @selected(optional($existing)->answer_text === $option)>{{ $option }}</option>
                                        @endforeach
                                    </select>
                                @else
                                    <textarea name="responses[{{ $loop->index }}][answer_text]" class="form-control text-start" rows="5"
                                        placeholder="Your answer...">{{ optional($existing)->answer_text }}</textarea>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-4">
                    <button type="submit" name="submit" value="0" class="btn btn-light-secondary rounded-pill px-3 small">
                        Save draft
                    </button>

                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        <button type="button" id="wizard-back" class="btn btn-light-secondary rounded-pill px-4" style="visibility:hidden">
                            <i class="bi bi-arrow-left"></i> Previous
                        </button>
                        <button type="button" id="wizard-next" class="btn btn-primary rounded-pill px-4" @if($questions->count() <= 1) style="display:none" @endif>
                            Next <i class="bi bi-arrow-right"></i>
                        </button>
                        <button type="button" id="wizard-submit" class="btn btn-primary rounded-pill px-4" @if($questions->count() > 1) style="display:none" @endif
                            data-bs-toggle="modal" data-bs-target="#confirm-submit-modal">
                            Submit review
                        </button>
                    </div>
                </div>

                <div class="text-center mt-4">
                    <a href="{{ route('my-reviews.index') }}" class="text-muted small text-decoration-none">← Back to My Reviews</a>
                </div>
            </div>

        </form>

        <div class="modal fade" id="confirm-submit-modal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg hr-modal">
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fw-bold">Submit review?</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body pt-2">
                        <p class="text-muted mb-0">Once submitted, you will not be able to edit this review anymore.</p>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-light-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" id="confirm-submit-btn" class="btn btn-primary rounded-pill px-4 fw-bold">Yes, submit</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <style>
        .review-wizard-wrap {
            max-width: 680px;
        }
        .review-answers-wrap {
            max-width: 960px;
        }
        .badge-q {
            display: inline-block;
            background: rgba(67, 97, 238, 0.12);
            color: #6685f7;
            font-weight: 700;
            font-size: 0.8rem;
            padding: 0.25rem 0.75rem;
            border-radius: 999px;
        }
        .review-question-card {
            border-radius: 1rem;
            background-color: var(--porta-surface);
            color: var(--porta-text);
        }
        .status-pill {
            display: inline-block;
            background: var(--porta-surface-muted);
            color: var(--porta-text-muted);
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.2rem 0.7rem;
            border-radius: 999px;
        }
        .status-pill.answered {
            background: rgba(31, 157, 85, 0.15);
            color: #35c274;
        }
        .question-text {
            max-width: 520px;
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--porta-text);
            line-height: 1.6;
        }
        .star-rating {
            direction: ltr;
        }
        .star-rating input[type="radio"] {
            display: none;
        }
        .star-rating .star-label {
            font-size: 2.25rem;
            color: var(--porta-border);
            cursor: pointer;
            transition: color 0.15s ease, transform 0.1s ease;
        }
        .star-rating .star-label:hover,
        .star-rating .star-label:hover ~ .star-label {
            color: #f5b301;
            transform: scale(1.05);
        }
        .star-rating input[type="radio"]:checked ~ .star-label {
            color: #f5b301;
        }
        .star-rating input[type="radio"]:disabled ~ .star-label {
            cursor: default;
        }
        .star-rating input[type="radio"]:disabled ~ .star-label:hover {
            transform: none;
        }
        #wizard-next:disabled,
        #wizard-next.disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
    </style>

    <script>
        (function () {
            var form = document.getElementById('review-wizard-form');
            if (!form) return;

            var steps = Array.prototype.slice.call(form.querySelectorAll('.wizard-step'));
            var total = steps.length;
            var current = 0;

            var currentEl = document.getElementById('wizard-current');
            var qBadgeEl = document.getElementById('wizard-qbadge');
            var progressBar = document.getElementById('wizard-progress-bar');
            var backBtn = document.getElementById('wizard-back');
            var nextBtn = document.getElementById('wizard-next');
            var submitBtn = document.getElementById('wizard-submit');

            function pad(n) {
                return n < 10 ? '0' + n : '' + n;
            }

            function isStepAnswered(step) {
                if (step.querySelector('input[type="radio"]')) {
                    return !!step.querySelector('input[type="radio"]:checked');
                }

                var select = step.querySelector('select');
                if (select) return select.value !== '';

                var textarea = step.querySelector('textarea');
                if (textarea) return textarea.value.trim() !== '';

                return true;
            }

            function updateStatusPill(step) {
                var pill = step.querySelector('.status-pill');
                if (!pill) return;
                var answered = isStepAnswered(step);
                pill.textContent = answered ? 'Answered' : 'Unanswered';
                pill.classList.toggle('answered', answered);
            }

            function updateNextButton() {
                if (!nextBtn) return;
                var answered = isStepAnswered(steps[current]);
                nextBtn.disabled = !answered;
                nextBtn.classList.toggle('disabled', !answered);
            }

            steps.forEach(function (step) {
                step.addEventListener('change', function () {
                    updateStatusPill(step);
                    updateNextButton();
                });
                step.addEventListener('input', function () {
                    updateStatusPill(step);
                    updateNextButton();
                });
            });

            function render() {
                steps.forEach(function (el, i) {
                    el.style.display = i === current ? '' : 'none';
                });

                currentEl.textContent = current + 1;
                qBadgeEl.textContent = 'Q' + pad(current + 1);
                progressBar.style.width = (((current + 1) / total) * 100) + '%';

                if (backBtn) backBtn.style.visibility = current > 0 ? 'visible' : 'hidden';
                if (nextBtn) nextBtn.style.display = current < total - 1 ? '' : 'none';
                if (submitBtn) submitBtn.style.display = current === total - 1 ? '' : 'none';

                updateNextButton();
            }

            if (nextBtn) {
                nextBtn.addEventListener('click', function () {
                    if (isStepAnswered(steps[current]) && current < total - 1) {
                        current++;
                        render();
                    }
                });
            }

            if (backBtn) {
                backBtn.addEventListener('click', function () {
                    if (current > 0) {
                        current--;
                        render();
                    }
                });
            }

            var confirmSubmitBtn = document.getElementById('confirm-submit-btn');
            if (confirmSubmitBtn) {
                confirmSubmitBtn.addEventListener('click', function () {
                    var hiddenSubmit = document.createElement('input');
                    hiddenSubmit.type = 'hidden';
                    hiddenSubmit.name = 'submit';
                    hiddenSubmit.value = '1';
                    form.appendChild(hiddenSubmit);
                    HTMLFormElement.prototype.submit.call(form);
                });
            }

            render();
        })();
    </script>
@endsection
