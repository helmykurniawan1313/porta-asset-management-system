@extends('layouts.app')

@section('title', 'Review Templates | Porta IT')
@section('header_title', 'Review Templates — ' . $reviewCycle->name)

@section('content')
    @if(session('status'))
        <div class="alert alert-light-success color-success d-flex align-items-center shadow-sm mb-4">
            <i class="bi bi-check-circle me-2"></i> Saved successfully.
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-light-danger color-danger d-flex align-items-center shadow-sm mb-4">
            <i class="bi bi-exclamation-triangle me-2"></i> {{ $errors->first() }}
        </div>
    @endif

    <a href="{{ route('hr.review-cycles.index') }}" class="btn btn-sm btn-light-secondary mb-3">
        <i class="bi bi-arrow-left"></i> Back to Cycles
    </a>

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-transparent border-0 pt-4 pb-0 d-flex justify-content-between align-items-center">
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
                            <form action="{{ route('hr.review-cycles.templates.destroy', [$reviewCycle, $template]) }}" method="POST" onsubmit="return confirm('Delete this template and all its questions?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-light-danger rounded-pill px-3"><i class="bi bi-trash"></i></button>
                            </form>
                        </div>
                    </div>

                    <table class="table table-sm align-middle mb-3">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Question</th>
                                <th>Type</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($template->questions->sortBy('order') as $question)
                            <tr>
                                <td>{{ $question->order }}</td>
                                <td>{{ $question->text }}</td>
                                <td><span class="badge bg-light-info text-info">{{ $question->type }}</span></td>
                                <td class="text-end">
                                    <div class="d-flex justify-content-end gap-2">
                                        <button data-bs-toggle="modal" data-bs-target="#edit-question-{{ $question->id }}" class="btn btn-sm btn-light-warning rounded-pill px-2"><i class="bi bi-pencil"></i></button>
                                        <form action="{{ route('hr.review-templates.questions.destroy', [$template, $question]) }}" method="POST" onsubmit="return confirm('Delete this question?')">
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
@endsection

@section('modals')
    <div class="modal fade" id="add-template-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary border-0">
                    <h5 class="modal-title text-white fw-bold">New Template</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('hr.review-cycles.templates.store', $reviewCycle) }}" method="POST">
                    @csrf
                    <div class="modal-body p-4">
                        <label class="form-label fw-bold small text-uppercase">Template Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="modal-footer bg-light border-0">
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
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary border-0">
                    <h5 class="modal-title text-white fw-bold">Edit Template</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('hr.review-cycles.templates.update', [$reviewCycle, $template]) }}" method="POST">
                    @csrf @method('PATCH')
                    <div class="modal-body p-4">
                        <label class="form-label fw-bold small text-uppercase">Template Name</label>
                        <input type="text" name="name" value="{{ $template->name }}" class="form-control" required>
                    </div>
                    <div class="modal-footer bg-light border-0">
                        <button type="button" class="btn btn-light-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary px-4 fw-bold">UPDATE</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="add-question-{{ $template->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary border-0">
                    <h5 class="modal-title text-white fw-bold">Add Question</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
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
                    <div class="modal-footer bg-light border-0">
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
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-primary border-0">
                    <h5 class="modal-title text-white fw-bold">Edit Question</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
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
                    <div class="modal-footer bg-light border-0">
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
