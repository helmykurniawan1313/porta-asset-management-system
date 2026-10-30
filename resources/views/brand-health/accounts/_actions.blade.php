{{-- Row / card actions menu for an account (Instagram, edit, delete). --}}
<div class="dropdown d-inline-block">
    <button type="button" class="btn btn-sm btn-light-secondary" data-bs-toggle="dropdown" aria-label="Actions"><i class="bi bi-three-dots"></i></button>
    <ul class="dropdown-menu dropdown-menu-end">
        @can('edit-brand-health')
            <li><a class="dropdown-item" href="#" onclick="instagram.open({{ $i }}); return false;"><i class="bi bi-instagram me-1"></i> {{ $a['ig_business_id'] ? 'Instagram ✓' : 'Connect Instagram' }}</a></li>
            <li><a class="dropdown-item" href="#" onclick="accountForm.open({{ $i }}); return false;">Edit</a></li>
            <li><a class="dropdown-item text-danger" href="#" onclick="accountForm.del({{ $i }}); return false;">Delete</a></li>
        @else
            <li><a class="dropdown-item" href="#" onclick="instagram.open({{ $i }}); return false;"><i class="bi bi-instagram me-1"></i> {{ $a['ig_business_id'] ? 'Instagram ✓' : 'Not connected' }}</a></li>
        @endcan
    </ul>
</div>
