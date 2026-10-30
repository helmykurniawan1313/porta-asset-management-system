@extends('brand-health.layout')

@section('title', 'Site Settings | Brand Health')
@section('header_title', 'Brand Health')

@section('bh-content')
<div class="bh-page-head">
    <div>
        <h3>Site Settings</h3>
        <p>Controls that affect the whole Brand Health section for every user.</p>
    </div>
</div>

<div class="bh-card" style="max-width: 40rem">
    <form method="POST" action="{{ route('brand-health.site-settings.update') }}" class="d-flex justify-content-between align-items-start gap-4">
        @csrf @method('PUT')
        <input type="hidden" name="maintenance_mode" value="{{ $maintenanceMode ? 0 : 1 }}">
        <div>
            <div class="fw-semibold">Under Construction Mode</div>
            <div class="bh-muted small mt-1">
                When on, everyone except Super Admins sees an under-construction page instead of Brand Health.
                The rest of Porta IT keeps working, and you can always come back here to turn it off.
            </div>
        </div>
        <div class="form-check form-switch fs-4 m-0">
            <input class="form-check-input" type="checkbox" role="switch" @checked($maintenanceMode) onchange="this.form.submit()" aria-label="Under Construction Mode">
        </div>
    </form>
    @if($maintenanceMode)
        <div class="d-flex align-items-center gap-2 rounded-3 border p-3 mt-3 small"
             style="border-color: var(--status-kurang-ink) !important; background: var(--status-kurang-bg); color: var(--status-kurang-ink)">
            <i class="bi bi-exclamation-triangle"></i>
            Brand Health is currently showing the under-construction page to everyone but Super Admins.
        </div>
    @endif
</div>
@endsection
