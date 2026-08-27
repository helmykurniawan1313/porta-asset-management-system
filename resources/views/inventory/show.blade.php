@extends('layouts.app')

@section('title', $device->name . ' | Asset Profile')
@section('header_title', 'Asset Profile')

@section('styles')
    <style>
        /* 1. Floating Header Table Style */
        .table-custom thead th {
            background-color: transparent !important;
            border-bottom: 2px solid rgba(67, 94, 190, 0.15) !important;
            color: #435ebe !important; 
            font-weight: 700 !important;
            text-transform: uppercase;
            font-size: 0.72rem;
            letter-spacing: 0.5px;
            padding: 1.2rem 1rem !important;
        }

        .theme-dark .table-custom thead th {
            color: #8c9eff !important;
            border-bottom: 2px solid rgba(255, 255, 255, 0.1) !important;
        }

        /* 2. Premium Card UI */
        .card {
            border-radius: 16px !important;
            box-shadow: 0 4px 24px rgba(67, 94, 190, 0.05) !important;
            transition: all 0.3s ease;
        }
        .card-header {
            padding: 1.5rem 1.5rem 1rem 1.5rem !important;
        }

        /* 3. Asset Avatar Styling */
        .asset-avatar {
            width: 90px;
            height: 90px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            margin: 0 auto;
        }

        /* 4. Premium Hover Effects */
        .hover-zoom {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .hover-zoom:hover {
            transform: scale(1.03);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important;
            cursor: pointer;
        }
        
        /* 5. Soft Hover for lists */
        .list-group-custom .list-group-item {
            transition: background-color 0.2s ease;
        }
        .list-group-custom .list-group-item:hover {
            background-color: rgba(67, 94, 190, 0.02);
        }

        /* 6. Timeline Log Styling */
        .timeline-box {
            background-color: transparent;
            border: 1px solid rgba(0,0,0,0.05);
            border-left: 3px solid #435ebe; 
            box-shadow: 0 2px 6px rgba(0,0,0,0.02);
            transition: all 0.2s ease;
        }
        .theme-dark .timeline-box {
            border-color: rgba(255,255,255,0.05);
            border-left: 3px solid #8c9eff;
        }
        .timeline-box:hover {
            transform: translateX(3px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        }

        /* 7. Perfect Optical Icon Alignment */
        .icon-fix {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            line-height: 0;
            transform: translateY(-0.06em);
        }

        /* 8. Interactive Accordion Chevron */
        .accordion-chevron {
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .collapse-toggle[aria-expanded="true"] .accordion-chevron {
            transform: rotate(-180deg);
        }

        /* 9. Empty State UI */
        .empty-state-icon {
            font-size: 2.5rem;
            color: rgba(67, 94, 190, 0.15);
            margin-bottom: 0.5rem;
        }
    </style>
@endsection

@section('content')
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
        <div>
            <a href="{{ route('inventory.index', $device->category) }}" class="btn btn-sm btn-light-primary px-3 fw-bold mb-3 d-inline-flex align-items-center gap-2 rounded-pill">
                <i class="bi bi-arrow-left icon-fix"></i> Back to {{ $device->category }}s
            </a>
            <div class="d-flex align-items-center gap-3">
                <h2 class="mb-0 fw-bold text-heading tracking-tight d-flex align-items-center">{{ $device->name }}</h2>
                <span class="badge {{ $device->user_id || $device->parent_id ? 'bg-light-success text-success' : 'bg-light-primary text-primary' }} px-3 py-2 rounded-pill fw-bold d-flex align-items-center">
                    {{ $device->user_id || $device->parent_id ? 'IN USE' : 'IN STOCK' }}
                </span>
            </div>
        </div>
        
        <div class="d-flex gap-2">
            <button data-bs-toggle="modal" data-bs-target="#edit-modal" class="btn btn-light-warning text-warning fw-bold px-4 shadow-sm d-flex align-items-center gap-2 rounded-pill">
                <i class="bi bi-pencil-square icon-fix"></i> EDIT
            </button>
            <form action="{{ route('inventory.destroy', $device->id) }}" method="POST" onsubmit="return confirmDelete(this, 'Permanently delete this asset?')" class="m-0">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-light-danger text-danger fw-bold px-4 shadow-sm d-flex align-items-center gap-2 h-100 rounded-pill">
                    <i class="bi bi-trash-fill icon-fix"></i> DELETE
                </button>
            </form>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-light-success color-success d-flex align-items-center gap-2 shadow-sm mb-4">
            <i class="bi bi-check-circle icon-fix fs-5"></i> {{ session('success') }}
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-light-danger color-danger d-flex align-items-center gap-2 shadow-sm mb-4">
            <i class="bi bi-exclamation-triangle icon-fix fs-5"></i> Action Failed: {{ $errors->first() }}
        </div>
    @endif

    @php
        $services = $device->serviceHistories()->latest()->paginate(5, ['*'], 'services')->withQueryString();
        $logs = $device->activityLogs()->latest()->paginate(5, ['*'], 'logs')->withQueryString();
        $serviceOpen = request()->has('services');
        $logsOpen = request()->has('logs');
        
        // Pre-calculate software allocations for the counter
        $deviceAllocations = \App\Models\SubscriptionAllocation::with('subscription.account')
            ->where('assignable_type', 'App\Models\Device')
            ->where('assignable_id', $device->id)
            ->get();
        $softwareCount = $deviceAllocations->count();
    @endphp

    <div class="row g-3 g-xl-4 mb-5">
        
        <div class="col-12 col-xl-4 d-flex flex-column gap-3">
            
            <div class="card border-0 overflow-hidden">
                <div class="card-body text-center p-5 position-relative">
                    
                    @if($device->image1)
                        <div class="mb-4">
                            <a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#image-lightbox-modal" onclick="document.getElementById('lightbox-image').src='{{ asset('storage/' . $device->image1) }}'">
                                <img src="{{ asset('storage/' . $device->image1) }}" alt="Primary Asset Image" class="img-fluid rounded shadow-sm hover-zoom" style="max-height: 200px; width: 100%; object-fit: cover;">
                            </a>
                        </div>
                    @else
                        <div class="asset-avatar bg-light-primary text-primary rounded-circle shadow-sm border mb-4">
                            @if($device->device_group == 'Device')
                                <i class="bi bi-pc-display icon-fix"></i>
                            @else
                                <i class="bi bi-cpu icon-fix"></i>
                            @endif
                        </div>
                    @endif

                    <h4 class="fw-bold text-uppercase tracking-tight mb-1">{{ $device->manufacturer ?? 'Unknown OEM' }}</h4>
                    <p class="text-muted fw-bold mb-0">{{ $device->model ?? 'Standard Edition' }}</p>
                </div>
            </div>

            <div class="card border-0">
                <div class="card-header bg-transparent border-0 pt-4 pb-0">
                    <h6 class="text-muted fw-bold text-uppercase small mb-0">Assignment & Specifications</h6>
                </div>
                <div class="card-body p-4">
                    
                    <div class="mb-4">
                        @if($device->device_group != 'Device' && $device->parentDevice)
                            <div class="d-flex align-items-center gap-3">
                                <div class="avatar avatar-lg bg-light-info text-info">
                                    <span class="avatar-content d-flex align-items-center justify-content-center"><i class="bi bi-pc-display fs-5 icon-fix"></i></span>
                                </div>
                                <div>
                                    <a href="{{ route('inventory.show', $device->parentDevice->id) }}" class="fw-bold text-heading fs-6 d-block">{{ $device->parentDevice->name }}</a>
                                    <p class="mb-0 text-muted fw-semibold small mt-1 d-flex align-items-center gap-2">
                                        <i class="bi bi-motherboard icon-fix"></i> Installed Component
                                    </p>
                                </div>
                            </div>
                        @elseif($device->user)
                            <div class="d-flex align-items-center gap-3">
                                <div class="avatar avatar-lg bg-light-primary text-primary">
                                    <span class="avatar-content fs-5 fw-bold d-flex align-items-center justify-content-center">{{ substr($device->user->name, 0, 1) }}</span>
                                </div>
                                <div>
                                    <a href="{{ route('employees.show', $device->user->id) }}" class="fw-bold text-heading fs-6 d-block">{{ $device->user->name }}</a>
                                    <p class="mb-0 text-muted fw-semibold small mt-1 d-flex align-items-center gap-2">
                                        <i class="bi bi-building icon-fix"></i> {{ $device->user->department->name ?? 'No Department' }}
                                    </p>
                                </div>
                            </div>
                        @else
                            <div class="text-center py-4 rounded-3 bg-transparent border border-2" style="border-style: dashed !important; border-color: rgba(67, 94, 190, 0.2) !important;">
                                <i class="bi bi-box-seam text-primary fs-3 mb-2 d-block opacity-50 icon-fix"></i>
                                <p class="mb-0 fw-bold text-heading">Available in Stock</p>
                                <small class="text-muted">Asset is ready for deployment</small>
                            </div>
                        @endif
                    </div>

                    <hr class="my-4" style="border-color: rgba(67, 94, 190, 0.1);">

                    @if(!in_array($device->category, ['PC', 'Laptop']))
                        <div class="p-3 bg-transparent rounded-3 mb-4 border shadow-sm">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="text-muted fw-bold text-uppercase small">{{ $device->category == 'Phone' ? 'Battery Health' : 'Hardware Health' }}</span>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="fw-black fs-5 {{ $device->current_health < 50 ? 'text-danger' : 'text-success' }}">{{ $device->current_health }}%</span>
                                    <button data-bs-toggle="modal" data-bs-target="#health-modal" class="btn btn-sm btn-light-secondary rounded-circle p-1 d-flex align-items-center justify-content-center" style="width: 24px; height: 24px;" title="Log Health Status">
                                        <i class="bi bi-plus icon-fix"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="progress progress-sm" style="height: 6px;">
                                <div class="progress-bar {{ $device->current_health < 50 ? 'bg-danger' : 'bg-success' }}" role="progressbar" style="width: {{ $device->current_health }}%"></div>
                            </div>
                        </div>
                    @endif

                    <ul class="list-group list-group-flush list-group-custom">
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 border-bottom py-3 bg-transparent">
                            <span class="text-muted fw-semibold small d-flex align-items-center gap-2"><i class="bi bi-key text-primary fs-5 icon-fix"></i> Password</span>
                            <span class="fw-bold text-heading">{{ $device->device_password ?? 'Not Set' }}</span>
                        </li>

                        @if($device->category == 'Phone')
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0 border-bottom py-3 bg-transparent">
                                <span class="text-muted fw-semibold small d-flex align-items-center gap-2"><i class="bi bi-memory text-primary fs-5 icon-fix"></i> Memory</span>
                                <span class="fw-bold text-heading">{{ $device->ram ?? '-' }} / {{ $device->rom ?? '-' }}</span>
                            </li>
                        @elseif(!in_array($device->category, ['PC', 'Laptop']))
                            <li class="list-group-item d-flex justify-content-between align-items-center px-0 border-bottom py-3 bg-transparent">
                                <span class="text-muted fw-semibold small d-flex align-items-center gap-2"><i class="bi bi-hdd text-primary fs-5 icon-fix"></i> Capacity</span>
                                <span class="fw-bold text-heading">{{ $device->size ?? '-' }}</span>
                            </li>
                        @endif
                        
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 border-bottom py-3 bg-transparent">
                            <span class="text-muted fw-semibold small d-flex align-items-center gap-2"><i class="bi bi-calendar-check text-primary fs-5 icon-fix"></i> Purchased</span>
                            <span class="fw-bold text-heading">{{ $device->purchase_date?->format('d M, Y') ?? 'Unknown' }}</span>
                        </li>
                        
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-3 bg-transparent">
                            <span class="text-muted fw-semibold small d-flex align-items-center gap-2"><i class="bi bi-currency-dollar text-primary fs-5 icon-fix"></i> Asset Value</span>
                            <span class="badge bg-light-primary text-primary px-3 py-2 rounded-pill fw-bold">Rp {{ number_format($device->price, 0) }}</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-8 d-flex flex-column gap-3">
            
            @if($device->device_group == 'Device')
            <div class="card border-0">
                <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center cursor-pointer collapse-toggle" data-bs-toggle="collapse" data-bs-target="#collapseComponents" aria-expanded="false">
                    <div>
                        <h5 class="card-title fw-bold mb-1 d-flex align-items-center gap-2">
                            System Components
                            <span class="badge bg-light-secondary text-secondary px-2 py-1 rounded-pill small" style="font-size: 0.75rem;">{{ $device->components->count() }}</span>
                        </h5>
                        <p class="text-muted small mb-0">Hardware installed inside this asset.</p>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <button data-bs-toggle="modal" data-bs-target="#attach-modal" class="btn btn-sm btn-light-primary text-primary fw-bold px-4 py-2 rounded-pill shadow-none d-flex align-items-center gap-2" onclick="event.stopPropagation();">
                            <i class="bi bi-link-45deg fs-5 icon-fix"></i> ATTACH PART
                        </button>
                        <i class="bi bi-chevron-down text-muted fs-5 accordion-chevron icon-fix"></i>
                    </div>
                </div>
                <div id="collapseComponents" class="collapse">
                    <div class="card-body py-4 border-top mt-3">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle table-custom mb-0">
                                <thead>
                                    <tr>
                                        <th>Component</th>
                                        <th class="text-center">Health</th>
                                        <th>Specs</th>
                                        <th class="text-end">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($device->components as $comp)
                                        <tr>
                                            <td class="py-3">
                                                <a href="{{ route('inventory.show', $comp->id) }}" class="fw-bold text-primary d-block">{{ $comp->name }}</a>
                                                <small class="text-muted fw-semibold">{{ $comp->category }} • {{ $comp->manufacturer }}</small>
                                            </td>
                                            <td class="text-center py-3">
                                                @if($comp->current_health !== null)
                                                    <span class="badge {{ $comp->current_health < 50 ? 'bg-light-danger text-danger' : 'bg-light-success text-success' }} px-3 py-2 rounded-pill">
                                                        {{ $comp->current_health }}%
                                                    </span>
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                            <td class="py-3">
                                                <div class="fw-bold text-heading">{{ $comp->model }}</div>
                                                <div class="text-muted small">{{ $comp->component_type }} • {{ $comp->size }}</div>
                                            </td>
                                            <td class="text-end py-3">
                                                <form action="{{ route('inventory.detach', $comp->id) }}" method="POST" class="d-flex justify-content-end align-items-center gap-2">
                                                    @csrf
                                                    <input type="date" name="action_date" value="{{ date('Y-m-d') }}" class="form-control form-control-sm w-auto d-inline-block border" required title="Detachment Date">
                                                    <button type="submit" class="btn btn-sm btn-light-danger text-danger fw-bold px-3 py-2 rounded-pill d-flex align-items-center">DETACH</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center py-5">
                                                <i class="bi bi-cpu empty-state-icon d-block"></i>
                                                <span class="text-muted fw-semibold small">No internal components registered.</span>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            @if(!in_array($device->category, ['PC', 'Laptop']))
            <div class="card border-0">
                <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center cursor-pointer collapse-toggle" data-bs-toggle="collapse" data-bs-target="#collapseHealth" aria-expanded="false">
                    <div>
                        <h5 class="card-title fw-bold mb-1 d-flex align-items-center gap-2">
                            Health Diagnostic Log
                        </h5>
                        <p class="text-muted small mb-0">Historical degradation tracking.</p>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <button data-bs-toggle="modal" data-bs-target="#health-modal" class="btn btn-sm btn-light-success text-success fw-bold px-4 py-2 rounded-pill shadow-none d-flex align-items-center gap-2" onclick="event.stopPropagation();">
                            <i class="bi bi-heart-pulse icon-fix"></i> LOG HEALTH
                        </button>
                        <i class="bi bi-chevron-down text-muted fs-5 accordion-chevron icon-fix"></i>
                    </div>
                </div>
                <div id="collapseHealth" class="collapse">
                    <div class="card-body py-4 border-top mt-3">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle table-custom mb-0">
                                <thead>
                                    <tr>
                                        <th>Recorded Date</th>
                                        <th class="text-center">Status</th>
                                        <th>Degradation Tracker</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php $previousHealth = null; @endphp
                                    @forelse($device->healthRecords ?? [] as $record)
                                        <tr>
                                            <td class="fw-bold text-heading py-3">{{ $record->recorded_date->format('M d, Y') }}</td>
                                            <td class="text-center py-3">
                                                <span class="badge {{ $record->health_percentage < 50 ? 'bg-light-danger text-danger' : 'bg-light-success text-success' }} px-3 py-2 rounded-pill">
                                                    {{ $record->health_percentage }}%
                                                </span>
                                            </td>
                                            <td class="fw-bold py-3">
                                                @if($previousHealth !== null)
                                                    @php $drop = $previousHealth - $record->health_percentage; @endphp
                                                    @if($drop > 0)
                                                        <span class="text-danger d-flex align-items-center gap-1"><i class="bi bi-arrow-down icon-fix"></i> -{{ $drop }}% drop</span>
                                                    @elseif($drop < 0)
                                                        <span class="text-success d-flex align-items-center gap-1"><i class="bi bi-arrow-up icon-fix"></i> +{{ abs($drop) }}% (Replaced)</span>
                                                    @else
                                                        <span class="text-muted">- Stable</span>
                                                    @endif
                                                @else
                                                    <span class="text-muted fst-italic fw-normal">Initial Record</span>
                                                @endif
                                            </td>
                                        </tr>
                                        @php $previousHealth = $record->health_percentage; @endphp
                                    @empty
                                        <tr>
                                            <td colspan="3" class="text-center py-5">
                                                <i class="bi bi-heart-pulse empty-state-icon d-block"></i>
                                                <span class="text-muted fw-semibold small">No health checks have been logged.</span>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <div class="card border-0">
                <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center cursor-pointer collapse-toggle" data-bs-toggle="collapse" data-bs-target="#collapseSoftware" aria-expanded="false">
                    <div>
                        <h5 class="card-title fw-bold mb-1 d-flex align-items-center gap-2">
                            Assigned Software & Licenses
                            <span class="badge bg-light-secondary text-secondary px-2 py-1 rounded-pill small" style="font-size: 0.75rem;">{{ $softwareCount }}</span>
                        </h5>
                        <p class="text-muted small mb-0">Subscriptions linked to this asset.</p>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <i class="bi bi-chevron-down text-muted fs-5 accordion-chevron icon-fix"></i>
                    </div>
                </div>
                <div id="collapseSoftware" class="collapse">
                    <div class="card-body py-4 border-top mt-3">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle table-custom mb-0">
                                <thead>
                                    <tr>
                                        <th>Software Plan</th>
                                        <th>Master Account</th>
                                        <th>Billing Cycle</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($deviceAllocations as $allocation)
                                        @if($allocation->subscription)
                                            <tr>
                                                <td class="py-3">
                                                    <a href="{{ route('subscriptions.show', $allocation->subscription->id) }}" class="fw-bold text-primary d-flex align-items-center gap-1">
                                                        {{ $allocation->subscription->plan }} <i class="bi bi-box-arrow-up-right small icon-fix"></i>
                                                    </a>
                                                    <span class="text-muted fw-semibold small">Software License</span>
                                                </td>
                                                <td class="fw-bold text-heading py-3">
                                                    {{ $allocation->subscription->account->email ?? 'Unlinked Account' }}
                                                </td>
                                                <td class="py-3">
                                                    <span class="badge bg-light-secondary text-secondary px-3 py-2 rounded-pill mb-1">
                                                        {{ strtoupper($allocation->subscription->period) }}
                                                    </span>
                                                    <div class="fw-bold text-muted small mt-1">
                                                        Next: {{ \Carbon\Carbon::parse($allocation->subscription->next_payment_date)->format('M d, Y') }}
                                                    </div>
                                                </td>
                                            </tr>
                                        @endif
                                    @empty
                                        <tr>
                                            <td colspan="3" class="text-center py-5">
                                                <i class="bi bi-cloud-slash empty-state-icon d-block"></i>
                                                <span class="text-muted fw-semibold small">No software subscriptions assigned.</span>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0">
                <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center cursor-pointer collapse-toggle" data-bs-toggle="collapse" data-bs-target="#collapseService" aria-expanded="{{ $serviceOpen ? 'true' : 'false' }}">
                    <div>
                        <h5 class="card-title fw-bold mb-1 d-flex align-items-center gap-2">
                            Service & Maint. Logs
                            <span class="badge bg-light-secondary text-secondary px-2 py-1 rounded-pill small" style="font-size: 0.75rem;">{{ $services->total() }}</span>
                        </h5>
                        <p class="text-muted small mb-0">Record of repairs and costs.</p>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <button data-bs-toggle="modal" data-bs-target="#service-modal" class="btn btn-sm btn-light-warning text-warning fw-bold px-4 py-2 rounded-pill shadow-none d-flex align-items-center gap-2" onclick="event.stopPropagation();">
                            <i class="bi bi-tools icon-fix"></i> LOG SERVICE
                        </button>
                        <i class="bi bi-chevron-down text-muted fs-5 accordion-chevron icon-fix"></i>
                    </div>
                </div>
                <div id="collapseService" class="collapse {{ $serviceOpen ? 'show' : '' }}">
                    <div class="card-body p-0 border-top mt-3">
                        <div class="d-flex justify-content-between align-items-center p-4 bg-transparent border-bottom">
                            <span class="fw-bold text-muted small text-uppercase">Total Cost: <span class="text-danger fs-5 ms-1">IDR {{ number_format($device->serviceHistories->sum('cost'), 2) }}</span></span>
                        </div>
                        <div id="ajax-service-container">
                            <ul class="list-group list-group-flush list-group-custom">
                                @forelse($services as $history)
                                    <li class="list-group-item p-4 bg-transparent">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <span class="fw-bold text-heading">{{ \Carbon\Carbon::parse($history->service_date)->format('M d, Y') }}</span>
                                            <span class="badge {{ $history->cost ? 'bg-light-danger text-danger' : 'bg-light-secondary text-secondary' }} px-3 py-2 rounded-pill">
                                                {{ $history->cost ? 'Rp '.number_format($history->cost, 0) : 'No Cost' }}
                                            </span>
                                        </div>
                                        <p class="mb-0 text-muted small">{{ $history->description }}</p>
                                    </li>
                                @empty
                                    <li class="list-group-item p-5 text-center border-0 bg-transparent">
                                        <i class="bi bi-wrench empty-state-icon d-block"></i>
                                        <span class="text-muted fw-semibold small">No service records found.</span>
                                    </li>
                                @endforelse
                            </ul>
                            @if($services->hasPages())
                                <div class="p-3 border-top bg-transparent">
                                    {{ $services->links() }}
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0">
                <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center cursor-pointer collapse-toggle" data-bs-toggle="collapse" data-bs-target="#collapseLifecycle" aria-expanded="{{ $logsOpen ? 'true' : 'false' }}">
                    <div>
                        <h5 class="card-title fw-bold mb-1 d-flex align-items-center gap-2">
                            Lifecycle History
                            <span class="badge bg-light-secondary text-secondary px-2 py-1 rounded-pill small" style="font-size: 0.75rem;">{{ $logs->total() }}</span>
                        </h5>
                        <p class="text-muted small mb-0">Complete audit trail for this asset.</p>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <i class="bi bi-chevron-down text-muted fs-5 accordion-chevron icon-fix"></i>
                    </div>
                </div>
                <div id="collapseLifecycle" class="collapse {{ $logsOpen ? 'show' : '' }}">
                    <div class="card-body p-0 border-top mt-3">
                        <div id="ajax-lifecycle-container">
                            <div class="p-4" style="max-height: 400px; overflow-y: auto;">
                                <ul class="list-group list-group-flush">
                                    @forelse($logs as $log)
                                        <li class="list-group-item px-0 border-0 d-flex align-items-start mb-3 bg-transparent">
                                            <div class="text-end me-3 mt-1" style="width: 50px;">
                                                <span class="text-primary fw-bold small d-block">{{ $log->created_at->format('M d') }}</span>
                                            </div>
                                            <div class="p-3 rounded-3 flex-grow-1 timeline-box">
                                                <h6 class="fw-bold text-heading small text-uppercase mb-1">{{ $log->action }}</h6>
                                                <p class="mb-0 text-muted small">{{ $log->description }}</p>
                                            </div>
                                        </li>
                                    @empty
                                        <li class="list-group-item text-center py-4 border-0 bg-transparent">
                                            <i class="bi bi-inbox empty-state-icon d-block"></i>
                                            <span class="text-muted fw-semibold small">No history records yet.</span>
                                        </li>
                                    @endforelse
                                </ul>
                            </div>
                            @if($logs->hasPages())
                                <div class="p-3 border-top bg-transparent">
                                    {{ $logs->links() }}
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            @if($device->image1 || $device->image2 || $device->image3)
            <div class="card border-0">
                <div class="card-header bg-transparent border-0 pt-4 pb-0">
                    <h5 class="card-title fw-bold mb-1">Asset Gallery</h5>
                    <p class="text-muted small mb-0">Visual documentation of this hardware.</p>
                </div>
                <div class="card-body py-4">
                    <div class="row g-3">
                        @foreach(['image1', 'image2', 'image3'] as $img)
                            @if($device->$img)
                            <div class="col-12 col-md-4">
                                <a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#image-lightbox-modal" onclick="document.getElementById('lightbox-image').src='{{ asset('storage/' . $device->$img) }}'" class="d-block">
                                    <img src="{{ asset('storage/' . $device->$img) }}" class="img-fluid rounded border shadow-sm hover-zoom" style="height: 200px; width: 100%; object-fit: cover;" alt="Asset Image">
                                </a>
                            </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

        </div>
    </div>
@endsection

@section('modals')

    <div class="modal fade" id="image-lightbox-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content bg-transparent border-0 shadow-none">
                <div class="modal-header border-0 pb-0">
                    <button type="button" class="btn-close btn-close-white ms-auto" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center p-0 mt-2">
                    <img id="lightbox-image" src="" class="img-fluid rounded shadow-lg border border-secondary" style="max-height: 85vh; object-fit: contain;">
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="edit-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg modal-fullscreen-md-down">
            <form action="{{ route('inventory.update', $device->id) }}" method="POST" enctype="multipart/form-data" class="w-100 h-100">
                <div class="modal-content border-0 shadow-sm h-100 hr-modal">
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fw-bold">Edit {{ $device->category }} Details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    
                    @csrf @method('PUT')
                    
                    <div class="modal-body p-4 p-md-5">
                        
                        @if(in_array($device->category, ['PC', 'Laptop']))
                            <div class="row g-3 mb-4">
                                <div class="col-12 col-md-6">
                                    <label class="form-label text-muted fw-bold small">Asset Name</label>
                                    <input type="text" name="name" value="{{ $device->name }}" class="form-control" required>
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label text-muted fw-bold small">Device Password</label>
                                    <input type="text" name="device_password" value="{{ $device->device_password }}" class="form-control">
                                </div>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-12 col-md-6">
                                    <label class="form-label text-muted fw-bold small">Manufacturer</label>
                                    <input type="text" name="manufacturer" value="{{ $device->manufacturer }}" class="form-control">
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label text-muted fw-bold small">Model</label>
                                    <input type="text" name="model" value="{{ $device->model }}" class="form-control">
                                </div>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-12">
                                    <label class="form-label text-muted fw-bold small">Assigned User</label>
                                    <select name="user_id" class="form-select">
                                        <option value="">-- Unassigned (STOCK) --</option>
                                        @foreach($employees as $emp)
                                            <option value="{{ $emp->id }}" {{ $device->user_id == $emp->id ? 'selected' : '' }}>{{ $emp->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-12 col-md-6">
                                    <label class="form-label text-muted fw-bold small">Purchase Date</label>
                                    <input type="date" name="purchase_date" value="{{ $device->purchase_date ? $device->purchase_date->format('Y-m-d') : '' }}" class="form-control">
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label text-muted fw-bold small">Asset Value</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light">Rp.</span>
                                        <input type="text" value="{{ number_format($device->price, 0) }}" oninput="formatPrice(this, 'edit_price_actual')" class="form-control">
                                        <input type="hidden" name="price" id="edit_price_actual" value="{{ $device->price }}">
                                    </div>
                                </div>
                            </div>

                        @elseif($device->category == 'Phone')
                            <div class="row g-3 mb-4">
                                <div class="col-12 col-md-6"><label class="form-label text-muted fw-bold small">Asset Name</label><input type="text" name="name" value="{{ $device->name }}" class="form-control" required></div>
                                <div class="col-12 col-md-6"><label class="form-label text-muted fw-bold small">Device Password</label><input type="text" name="device_password" value="{{ $device->device_password }}" class="form-control"></div>
                            </div>
                            <div class="row g-3 mb-4">
                                <div class="col-12 col-md-6"><label class="form-label text-muted fw-bold small">Manufacturer</label><input type="text" name="manufacturer" value="{{ $device->manufacturer }}" class="form-control"></div>
                                <div class="col-12 col-md-6"><label class="form-label text-muted fw-bold small">Model</label><input type="text" name="model" value="{{ $device->model }}" class="form-control"></div>
                            </div>
                            <div class="row g-3 mb-4">
                                <div class="col-12">
                                    <label class="form-label text-muted fw-bold small">Assigned User</label>
                                    <select name="user_id" class="form-select">
                                        <option value="">-- Unassigned (STOCK) --</option>
                                        @foreach($employees as $emp) <option value="{{ $emp->id }}" {{ $device->user_id == $emp->id ? 'selected' : '' }}>{{ $emp->name }}</option> @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="row g-3 mb-4">
                                <div class="col-12 col-md-4"><label class="form-label text-muted fw-bold small">RAM</label><input type="text" name="ram" value="{{ $device->ram }}" class="form-control"></div>
                                <div class="col-12 col-md-4"><label class="form-label text-muted fw-bold small">ROM</label><input type="text" name="rom" value="{{ $device->rom }}" class="form-control"></div>
                                <div class="col-12 col-md-4"><label class="form-label text-muted fw-bold small">Health %</label><input type="number" name="current_health" value="{{ $device->current_health }}" class="form-control"></div>
                            </div>
                            <div class="row g-3 mb-4">
                                <div class="col-12 col-md-6"><label class="form-label text-muted fw-bold small">Purchase Date</label><input type="date" name="purchase_date" value="{{ $device->purchase_date ? $device->purchase_date->format('Y-m-d') : '' }}" class="form-control"></div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label text-muted fw-bold small">Asset Value</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light">Rp.</span><input type="text" value="{{ number_format($device->price, 0) }}" oninput="formatPrice(this, 'edit_price_actual')" class="form-control"><input type="hidden" name="price" id="edit_price_actual" value="{{ $device->price }}">
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="row g-3 mb-4">
                                <div class="col-12 col-md-6"><label class="form-label text-muted fw-bold small">Asset Name</label><input type="text" name="name" value="{{ $device->name }}" class="form-control" required></div>
                                <div class="col-12 col-md-6"><label class="form-label text-muted fw-bold small">Component Type</label><input type="text" name="component_type" value="{{ $device->component_type }}" class="form-control"></div>
                            </div>
                            <div class="row g-3 mb-4">
                                <div class="col-12 col-md-6"><label class="form-label text-muted fw-bold small">Manufacturer</label><input type="text" name="manufacturer" value="{{ $device->manufacturer }}" class="form-control"></div>
                                <div class="col-12 col-md-6"><label class="form-label text-muted fw-bold small">Model</label><input type="text" name="model" value="{{ $device->model }}" class="form-control"></div>
                            </div>
                            <div class="row g-3 mb-4">
                                <div class="col-12 col-md-6"><label class="form-label text-muted fw-bold small">Size / Specs</label><input type="text" name="size" value="{{ $device->size }}" class="form-control"></div>
                                <div class="col-12 col-md-6"><label class="form-label text-muted fw-bold small">Health %</label><input type="number" name="current_health" value="{{ $device->current_health }}" class="form-control"></div>
                            </div>
                            
                            <div class="row g-3 mb-4">
                                <div class="col-12 col-md-6">
                                    <label class="form-label text-muted fw-bold small">Assigned User</label>
                                    <select name="user_id" class="form-select">
                                        <option value="">-- Unassigned (STOCK) --</option>
                                        @foreach($employees as $emp)
                                            <option value="{{ $emp->id }}" {{ $device->user_id == $emp->id ? 'selected' : '' }}>{{ $emp->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label text-muted fw-bold small">Attach to Device</label>
                                    <select name="parent_id" class="form-select">
                                        <option value="">-- Unassigned (STOCK) --</option>
                                        @foreach(\App\Models\Device::whereIn('category', ['PC', 'Laptop', 'Phone'])->get() as $targetDevice)
                                            <option value="{{ $targetDevice->id }}" {{ $device->parent_id == $targetDevice->id ? 'selected' : '' }}>{{ $targetDevice->name }} ({{ $targetDevice->category }})</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-12 col-md-6"><label class="form-label text-muted fw-bold small">Purchase Date</label><input type="date" name="purchase_date" value="{{ $device->purchase_date ? $device->purchase_date->format('Y-m-d') : '' }}" class="form-control"></div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label text-muted fw-bold small">Asset Value</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light">Rp.</span><input type="text" value="{{ number_format($device->price, 0) }}" oninput="formatPrice(this, 'edit_price_actual')" class="form-control"><input type="hidden" name="price" id="edit_price_actual" value="{{ $device->price }}">
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if($device->image1 || $device->image2 || $device->image3)
                        <div class="row g-3 border-top pt-3 mt-1 mb-3">
                            <div class="col-12">
                                <label class="form-label text-muted fw-bold small">Current Gallery (Click X to remove)</label>
                                <div class="d-flex gap-3 mt-2">
                                    @foreach(['image1', 'image2', 'image3'] as $imgField)
                                        @if($device->$imgField)
                                            <div class="position-relative" id="preview-{{ $device->id }}-{{ $imgField }}">
                                                <img src="{{ asset('storage/' . $device->$imgField) }}" class="rounded shadow-sm border" style="width: 75px; height: 75px; object-fit: cover;">
                                                <button type="button" onclick="markImageForDeletion('{{ $device->id }}', '{{ $imgField }}')" class="btn btn-danger position-absolute top-0 start-100 translate-middle rounded-circle shadow-sm p-0 d-flex align-items-center justify-content-center" style="width: 22px; height: 22px; margin-left: -5px; margin-top: 5px;">
                                                    <i class="bi bi-x" style="font-size: 14px; line-height: 1;"></i>
                                                </button>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                                <div id="hidden-removals-{{ $device->id }}"></div>
                            </div>
                        </div>
                        @endif

                        <div class="row g-3 @if(!$device->image1 && !$device->image2 && !$device->image3) border-top pt-3 mt-1 @endif">
                            <div class="col-12">
                                <label class="form-label text-muted fw-bold small">Upload New Images (Max 3 - Auto-optimized to WEBP)</label>
                                <input type="file" name="images[]" multiple accept="image/*" class="form-control" onchange="validateFiles(this)">
                            </div>
                        </div>

                    </div>
                    <div class="modal-footer border-0">
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end w-100">
                            <button type="button" class="btn btn-light-secondary rounded-pill order-2 order-md-1" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm order-1 order-md-2">Save Changes</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="attach-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-sm hr-modal">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Attach Component</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('inventory.attach') }}" method="POST">
                    @csrf
                    <input type="hidden" name="parent_id" value="{{ $device->id }}">
                    <div class="modal-body p-4">
                        <div class="mb-4">
                            <label class="form-label text-muted fw-bold small">Installation Date</label>
                            <input type="date" name="action_date" value="{{ date('Y-m-d') }}" class="form-control" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label text-muted fw-bold small">Select Part from Stock</label>
                            <select name="child_id" class="form-select" required>
                                <option value="">-- Available Stock Parts --</option>
                                @foreach($stockItems as $stock)
                                    <option value="{{ $stock->id }}">{{ $stock->category }}: {{ $stock->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-light-secondary rounded-pill" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm">Complete Installation</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="service-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-sm hr-modal">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Log Maintenance/Service</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('inventory.service', $device->id) }}" method="POST">
                    @csrf
                    <div class="modal-body p-4">
                        <div class="row mb-4">
                            <div class="col-6">
                                <label class="form-label text-muted fw-bold small">Service Date</label>
                                <input type="date" name="log_date" value="{{ date('Y-m-d') }}" class="form-control" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label text-muted fw-bold small">Cost</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light">Rp.</span>
                                    <input type="number" step="0.01" name="cost" class="form-control" placeholder="0.00">
                                </div>
                            </div>
                        </div>
                        <div class="mb-2">
                            <label class="form-label text-muted fw-bold small">Description of Work</label>
                            <textarea name="description" rows="3" class="form-control" placeholder="e.g., Replaced cracked screen..." required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-light-secondary rounded-pill" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning rounded-pill text-white px-4 fw-bold shadow-sm">Save Log</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="health-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-sm hr-modal">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Log Health Diagnostic</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form action="{{ route('inventory.health', $device->id) }}" method="POST">
                    @csrf
                    <div class="modal-body p-4">
                        <div class="mb-4">
                            <label class="form-label text-muted fw-bold small">Diagnostic Date</label>
                            <input type="date" name="recorded_date" value="{{ date('Y-m-d') }}" class="form-control" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label text-muted fw-bold small">New Health %</label>
                            <input type="number" name="health_percentage" value="{{ $device->current_health }}" class="form-control" min="0" max="100" required>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-light-secondary rounded-pill" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success rounded-pill px-4 fw-bold shadow-sm text-white">Save Record</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        function setupAjaxPagination(containerId) {
            const container = document.getElementById(containerId);
            if (!container) return;

            container.addEventListener('click', function (e) {
                const link = e.target.closest('a.page-link');
                if (link && link.href) {
                    e.preventDefault();
                    container.style.opacity = '0.5';
                    container.style.pointerEvents = 'none';

                    fetch(link.href)
                        .then(response => response.text())
                        .then(html => {
                            const parser = new DOMParser();
                            const doc = parser.parseFromString(html, 'text/html');
                            container.innerHTML = doc.getElementById(containerId).innerHTML;
                            container.style.opacity = '1';
                            container.style.pointerEvents = 'auto';
                            window.history.pushState(null, '', link.href);
                        })
                        .catch(error => {
                            console.error('AJAX Pagination Error:', error);
                            container.style.opacity = '1';
                            container.style.pointerEvents = 'auto';
                        });
                }
            });
        }

        setupAjaxPagination('ajax-service-container');
        setupAjaxPagination('ajax-lifecycle-container');
    });

    // Price formatting logic handling dynamic hidden field IDs
    function formatPrice(input, hiddenFieldId) {
        let value = input.value.replace(/\D/g, "");
        if (value !== "") {
            input.value = parseInt(value).toLocaleString('en-US');
            if(document.getElementById(hiddenFieldId)) {
                document.getElementById(hiddenFieldId).value = value;
            }
        } else {
            if(document.getElementById(hiddenFieldId)) {
                document.getElementById(hiddenFieldId).value = "";
            }
        }
    }

    // File count validation
    function validateFiles(input) {
        if (input.files.length > 3) {
            alert("Maximum 3 images allowed!");
            input.value = "";
        }
    }

    // Hide image and append to hidden removals list
    function markImageForDeletion(itemId, imageField) {
        document.getElementById('preview-' + itemId + '-' + imageField).style.display = 'none';
        const hiddenContainer = document.getElementById('hidden-removals-' + itemId);
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'remove_images[]';
        input.value = imageField;
        hiddenContainer.appendChild(input);
    }
    </script>
@endsection