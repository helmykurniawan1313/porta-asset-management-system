@extends('layouts.app')

@section('title', 'Review Templates | Porta IT')
@section('header_title', 'Review Templates — ' . $reviewCycle->name)

@section('content')
    @if($errors->any())
        <div class="alert alert-light-danger color-danger d-flex align-items-center shadow-sm mb-4">
            <i class="bi bi-exclamation-triangle me-2"></i> {{ $errors->first() }}
        </div>
    @endif

    <a href="{{ route('hr.review-cycles.index') }}" class="btn btn-sm btn-light-secondary mb-3">
        <i class="bi bi-arrow-left"></i> Back to Cycles
    </a>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-transparent border-0 pt-4 pb-0 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
            <h5 class="card-title mb-1">Templates</h5>
            <button data-bs-toggle="modal" data-bs-target="#add-template-modal" class="btn btn-primary btn-sm px-4 fw-bold shadow-sm">
                <i class="bi bi-plus-circle me-1"></i> NEW TEMPLATE
            </button>
        </div>
        <div class="card-body py-4">
            @forelse($reviewTemplates as $template)
            <div class="card border mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <h6 class="fw-bold mb-0">{{ $template->name }}</h6>
                        <div class="d-flex gap-2">
                            <button data-bs-toggle="modal" data-bs-target="#edit-template-{{ $template->id }}" class="btn btn-sm btn-light-warning rounded-pill px-3">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <form action="{{ route('hr.review-cycles.templates.destroy', [$reviewCycle, $template]) }}" method="POST" onsubmit="return confirmDelete(this, 'Delete this template and all its questions?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-light-danger rounded-pill px-3"><i class="bi bi-trash"></i></button>
                            </form>
                        </div>
                    </div>

                    <div class="table-responsive">
                    <table class="table table-sm align-middle mb-3">
                        <thead>
                            <tr>
                                <th style="width: 2rem;"></th>
                                <th>Question</th>
                                <th>Type</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="question-sortable" data-reorder-url="{{ route('hr.review-templates.questions.reorder', $template) }}">
                            @forelse($template->questions->sortBy('order') as $question)
                            <tr draggable="true" data-question-id="{{ $question->id }}" class="question-row">
                                <td class="text-muted drag-handle" style="cursor: grab;"><i class="bi bi-grip-vertical"></i></td>
                                <td>{{ $question->text }}</td>
                                <td><span class="badge question-type-badge">{{ $question->type }}</span></td>
                                <td class="text-end">
                                    <div class="d-flex justify-content-end gap-2">
                                        <button data-bs-toggle="modal" data-bs-target="#edit-question-{{ $question->id }}" class="btn btn-sm btn-light-warning rounded-pill px-2"><i class="bi bi-pencil"></i></button>
                                        <form action="{{ route('hr.review-templates.questions.destroy', [$template, $question]) }}" method="POST" onsubmit="return confirmDelete(this, 'Delete this question?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-light-danger rounded-pill px-2"><i class="bi bi-trash"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="text-center text-muted">No questions yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                    </div>

                    <button data-bs-toggle="modal" data-bs-target="#add-question-{{ $template->id }}" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-plus"></i> Add Question
                    </button>
                </div>
            </div>
            @empty
            <p class="text-center text-muted py-4">No templates yet for this cycle.</p>
            @endforelse
        </div>
    </div>

    <style>
        .question-type-badge {
            background: rgba(99, 179, 237, 0.15);
            color: #63b3ed;
            font-weight: 600;
        }
        .question-row.dragging {
            opacity: 0.4;
        }
        .question-row .drag-handle {
            user-select: none;
        }
    </style>
@endsection

@section('modals')
    <div class="modal fade" id="add-template-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg hr-modal">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">New Template</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('hr.review-cycles.templates.store', $reviewCycle) }}" method="POST">
                    @csrf
                    <div class="modal-body p-4">
                        <label class="form-label fw-bold small text-uppercase">Template Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-light-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary px-4 fw-bold">SAVE</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @foreach($reviewTemplates as $template)
    <div class="modal fade" id="edit-template-{{ $template->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg hr-modal">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Edit Template</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('hr.review-cycles.templates.update', [$reviewCycle, $template]) }}" method="POST">
                    @csrf @method('PATCH')
                    <div class="modal-body p-4">
                        <label class="form-label fw-bold small text-uppercase">Template Name</label>
                        <input type="text" name="name" value="{{ $template->name }}" class="form-control" required>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-light-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary px-4 fw-bold">UPDATE</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="add-question-{{ $template->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg hr-modal">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Add Question</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('hr.review-templates.questions.store', $template) }}" method="POST">
                    @csrf
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-uppercase">Question Text</label>
                            <textarea name="text" class="form-control" rows="2" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-uppercase">Type</label>
                            <select name="type" class="form-select">
                                <option value="rating_scale">Rating Scale</option>
                                <option value="text">Text</option>
                                <option value="multiple_choice">Multiple Choice</option>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label fw-bold small text-uppercase">Order</label>
                            <input type="number" name="order" class="form-control" value="0">
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-light-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary px-4 fw-bold">SAVE</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @foreach($template->questions as $question)
    <div class="modal fade" id="edit-question-{{ $question->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg hr-modal">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Edit Question</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('hr.review-templates.questions.update', [$template, $question]) }}" method="POST">
                    @csrf @method('PATCH')
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-uppercase">Question Text</label>
                            <textarea name="text" class="form-control" rows="2" required>{{ $question->text }}</textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-uppercase">Type</label>
                            <select name="type" class="form-select">
                                @foreach(['rating_scale' => 'Rating Scale', 'text' => 'Text', 'multiple_choice' => 'Multiple Choice'] as $val => $label)
                                    <option value="{{ $val }}" {{ $question->type === $val ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label fw-bold small text-uppercase">Order</label>
                            <input type="number" name="order" class="form-control" value="{{ $question->order }}">
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-light-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary px-4 fw-bold">UPDATE</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endforeach
    @endforeach
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            document.querySelectorAll('.question-sortable').forEach(function (tbody) {
                var dragging = null;

                tbody.addEventListener('dragstart', function (e) {
                    var row = e.target.closest('.question-row');
                    if (!row) return;
                    dragging = row;
                    row.classList.add('dragging');
                    e.dataTransfer.effectAllowed = 'move';
                });

                tbody.addEventListener('dragend', function (e) {
                    var row = e.target.closest('.question-row');
                    if (row) row.classList.remove('dragging');
                    dragging = null;
                });

                tbody.addEventListener('dragover', function (e) {
                    e.preventDefault();
                    if (!dragging) return;

                    var target = e.target.closest('.question-row');
                    if (!target || target === dragging) return;

                    var rect = target.getBoundingClientRect();
                    var before = (e.clientY - rect.top) < rect.height / 2;
                    tbody.insertBefore(dragging, before ? target : target.nextSibling);
                });

                tbody.addEventListener('drop', function (e) {
                    e.preventDefault();
                    persistOrder(tbody, csrfToken);
                });
            });

            function persistOrder(tbody, csrfToken) {
                var rows = Array.prototype.slice.call(tbody.querySelectorAll('.question-row'));
                var payload = rows.map(function (row, i) {
                    return { id: parseInt(row.dataset.questionId, 10), order: i };
                });

                fetch(tbody.dataset.reorderUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ questions: payload }),
                }).then(function (response) {
                    if (!response.ok) {
                        alert('Could not save the new order (server responded ' + response.status + '). The page will reload.');
                    }
                    window.location.reload();
                }).catch(function () {
                    alert('Could not save the new order (network error). The page will reload.');
                    window.location.reload();
                });
            }
        });
    </script>
@endsection
