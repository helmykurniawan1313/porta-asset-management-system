@extends('brand-health.layout')

@section('title', 'Brand Health | Under maintenance')
@section('header_title', 'Brand Health')

@section('bh-content')
<div class="bh-card text-center py-5">
    <i class="bi bi-cone-striped" style="font-size: 3.5rem; color: var(--status-cukup-ink)"></i>
    <h3 class="fw-bold mt-3">We'll be right back</h3>
    <p class="bh-muted mx-auto mb-0" style="max-width: 28rem">
        Brand Health is currently undergoing maintenance. Thanks for your patience — please check back shortly.
        The rest of Porta IT is working as usual.
    </p>
</div>
@endsection
