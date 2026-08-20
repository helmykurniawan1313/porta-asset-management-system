@extends('layouts.app')

@section('title', 'Review Report | Porta IT')
@section('header_title', 'Review Report — ' . $employee['name'])

@section('content')
    <a href="{{ route('hr.employee-reviews.show', $employee['id']) }}" class="btn btn-sm btn-light-secondary mb-3">
        <i class="bi bi-arrow-left"></i> Back to Review History
    </a>

    <div class="d-flex justify-content-end mb-3">
        <a href="{{ route('hr.employee-reviews.report.download', $employee['id']) }}" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-file-earmark-pdf me-1"></i> Download PDF
        </a>
    </div>

    @include('performance-review.partials.report-sections')
@endsection
