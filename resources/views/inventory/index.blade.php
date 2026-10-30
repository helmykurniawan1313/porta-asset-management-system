@extends('layouts.app')

@section('title', 'Porta Inventory | ' . $category)
@section('header_title', $category)

@section('styles')
    <link rel="stylesheet" href="{{ asset('assets/extensions/datatables.net-bs5/css/dataTables.bootstrap5.min.css') }}">
    <link rel="stylesheet" crossorigin href="{{ asset('assets/compiled/css/table-datatable-jquery.css') }}">
    <style>
        /* Smooth hover for image previews */
        .hover-opacity-100 { opacity: 0; transition: all 0.3s ease-in-out; }
        .hover-parent:hover .hover-opacity-100 { opacity: 1; background-color: rgba(0,0,0,0.6) !important; }
        

    </style>
 
@endsection

@section('content')
    @php $isNetwork = in_array($category, \App\Models\Device::NETWORK_CATEGORIES); @endphp

    @if($category == 'Dashboard')
    <div class="row mb-4">
        @php 
            $totalAssets = \App\Models\Device::count();
            $lowHealth = \App\Models\Device::where('current_health', '<', 50)->where('device_group', 'Peripheral')->count();
        @endphp
        
        <div class="col-12 col-md-6 col-lg-4 mb-3 mb-lg-0">
            <div class="card shadow-sm h-100 border-0">
                <div class="card-body d-flex align-items-center p-4">
                    <div class="stats-icon blue mb-2 me-3">
                        <i class="bi bi-box-seam fs-3"></i>
                    </div>
                    <div>
                        <h6 class="text-muted fw-semibold mb-1" style="font-size: 0.85rem; letter-spacing: 0.5px;">TOTAL ASSETS</h6>
                        <h3 class="fw-bolder mb-0">{{ $totalAssets }}</h3>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-12 col-md-6 col-lg-4 mb-3 mb-lg-0">
            <div class="card shadow-sm h-100 border-0">
                <div class="card-body d-flex align-items-center p-4">
                    <div class="stats-icon red mb-2 me-3">
                        <i class="bi bi-activity fs-3"></i>
                    </div>
                    <div>
                        <h6 class="text-muted fw-semibold mb-1" style="font-size: 0.85rem; letter-spacing: 0.5px;">LOW HEALTH (PERIPHERALS)</h6>
                        <h3 class="text-danger fw-bolder mb-0">{{ $lowHealth }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <section class="section">
        <div class="card shadow-sm border-0">
            
            <div class="card-header border-bottom bg-transparent py-4 d-flex flex-column flex-md-row justify-content-between align-items-md-center">
                <div class="mb-3 mb-md-0">
                    <h5 class="card-title mb-1 text-capitalize">{{ $category }} Overview</h5>
                    <p class="text-muted mb-0" style="font-size: 0.85rem;">
                        Found <span class="fw-bold text-primary">{{ count($items) }}</span> registered records.
                    </p>
                </div>
                
                <div class="d-flex gap-2">
                    <a href="{{ route('inventory.export', $category) }}" class="btn btn-light-secondary btn-sm px-3">
                        <i class="bi bi-file-earmark-excel me-1"></i> Export Excel
                    </a>

                    @if($category !== 'Dashboard')
                    <button data-bs-toggle="modal" data-bs-target="#add-modal" class="btn btn-primary btn-sm px-4 fw-bold shadow-sm">
                        <i class="bi bi-plus-lg me-1"></i> New {{ $category }}
                    </button>
                    @endif
                </div>
            </div>

            <div class="card-body py-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle" id="table1">
            <thead>
                {{-- Clean, Transparent Header --}}
                <tr>
                    <th>NAME</th>
                    @if($isNetwork)
                        <th>DEVICE</th>
                        <th>LOCATION</th>
                        @if($category != 'Switch')<th>WI-FI (SSID)</th>@endif
                        <th>IP ADDRESS</th>
                        <th>CONDITION</th>
                        <th>STATUS</th>
                    @elseif($category == 'Phone')
                        <th>DEVICE</th>
                        <th>OS</th>
                        <th>RAM / STORAGE</th>
                        <th>COLOR</th>
                        <th class="text-center">BATTERY</th>
                        <th>CONDITION</th>
                        <th>USER</th>
                    @else
                    <th>MANUFACTURE</th>
                    <th>MODEL</th>
                    @endif

                    @if(in_array($category, ['PC', 'Laptop', 'Dashboard']))
                        <th>USER</th>
                    @elseif($category == 'Phone' || $isNetwork)
                    @else
                        <th>TYPE</th>
                        <th>SIZE</th>
                        <th class="text-center">HEALTH</th>
                        <th>DATE</th>
                    @endif
                    <th class="text-end">ACTIONS</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $item)
                <tr>
                    @php
                        $itemIssues = $item->activeIssues->pluck('description')
                            ->merge($item->components->flatMap(fn ($c) => $c->activeIssues->map(fn ($i) => $c->name.': '.$i->description)));
                    @endphp
                    <td class=" py-3 text-nowrap">
                        {{ $item->name }}
                        @if($itemIssues->count())
                            <span class="badge bg-light-warning text-warning ms-1" data-bs-toggle="tooltip" title="{{ $itemIssues->implode(' • ') }}">
                                <i class="bi bi-exclamation-triangle-fill"></i> {{ $itemIssues->count() }}
                            </span>
                        @endif
                    </td>
                    @if($isNetwork)
                        <td class="py-3">
                            <div class="fw-semibold">{{ trim(($item->manufacturer ?? '').' '.($item->model ?? '')) ?: '-' }}</div>
                            @if($item->color)<small class="text-muted">{{ $item->color }}</small>@endif
                        </td>
                        <td class="py-3">
                            <div>{{ $item->location ?? '-' }}</div>
                            @if($item->area)<small class="text-muted">Serves: {{ $item->area }}</small>@endif
                        </td>
                        @if($category != 'Switch')
                            <td class="py-3">
                                @if($item->ssid)<i class="bi bi-wifi text-primary me-1"></i>{{ $item->ssid }}@else - @endif
                            </td>
                        @endif
                        <td class="py-3 font-monospace small">{{ $item->ip_address ?? '-' }}</td>
                        <td class="py-3">
                            @php
                                $conditionClass = match (strtolower($item->condition ?? '')) {
                                    'sangat baik', 'baik' => 'bg-light-success text-success',
                                    'ok' => 'bg-light-primary text-primary',
                                    'cukup' => 'bg-light-warning text-warning',
                                    default => 'bg-light-secondary text-secondary',
                                };
                            @endphp
                            @if($item->condition)<span class="badge {{ $conditionClass }}">{{ $item->condition }}</span>@else - @endif
                        </td>
                        <td class="py-3 text-nowrap">
                            @if(is_null($item->in_use)) -
                            @elseif($item->in_use)<span class="badge bg-light-success text-success">In use</span>
                            @else<span class="badge bg-light-secondary text-secondary">Not in use</span>@endif
                        </td>
                    @elseif($category == 'Phone')
                        <td class="py-3" data-order="{{ $item->manufacturer }} {{ $item->model }}">
                            <div class="fw-semibold">{{ trim(($item->manufacturer ?? '').' '.($item->model ?? '')) ?: '-' }}</div>
                            @if($item->processor)<small class="text-muted">{{ $item->processor }}</small>@endif
                        </td>
                        <td class="py-3 text-nowrap">
                            @if($item->os)
                                <i class="bi {{ strtolower($item->os) == 'ios' ? 'bi-apple' : 'bi-android2 text-success' }} me-1"></i>{{ $item->os }}
                                @if($item->os_version)<small class="text-muted">{{ $item->os_version }}</small>@endif
                            @else - @endif
                        </td>
                        <td class="text-primary fw-semibold py-3 text-nowrap">{{ $item->ram ?? '-' }} / {{ $item->rom ?? '-' }}</td>
                        <td class="py-3">{{ $item->color ?? '-' }}</td>
                        <td class="text-center py-3" data-order="{{ $item->current_health ?? -1 }}">
                            @if(!is_null($item->current_health))
                                <span class="badge {{ $item->current_health < 80 ? 'bg-light-danger text-danger' : 'bg-light-success text-success' }} px-2 py-1">{{ $item->current_health }}%</span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td class="py-3 text-nowrap">
                            @if($item->condition)
                                @php
                                    $conditionClass = match (strtolower($item->condition)) {
                                        'sangat baik', 'baik' => 'bg-light-success text-success',
                                        'ok' => 'bg-light-primary text-primary',
                                        'cukup' => 'bg-light-warning text-warning',
                                        default => 'bg-light-secondary text-secondary',
                                    };
                                @endphp
                                <span class="badge {{ $conditionClass }}">{{ $item->condition }}</span>
                            @else - @endif
                            @if($item->usability_tier)<small class="text-muted d-block">Tier {{ $item->usability_tier }}/10</small>@endif
                        </td>
                        <td class="py-3">{{ $item->user->name ?? 'STOCK' }}</td>
                    @else
                    <td class="text-muted py-3">{{ $item->manufacturer ?? '-' }}</td>
                    <td class="py-3">{{ $item->model ?? '-' }}</td>
                    @endif

                    @if(in_array($category, ['PC', 'Laptop', 'Dashboard']))
                        <td class="py-3">
                            <span class=" px-2 py-1">
                                {{ $item->user->name ?? 'STOCK' }}
                            </span>
                        </td>
                    @elseif($category == 'Phone' || $isNetwork)
                    @else
                        <td class="text-primary fw-semibold py-3">{{ $item->component_type ?? '-' }}</td>
                        <td class="text-muted py-3">{{ $item->size ?? '-' }}</td>
                        <td class="text-center py-3" data-order="{{ $item->current_health ?? -1 }}">
                            @if(!is_null($item->current_health))
                                <span class="badge {{ $item->current_health < 50 ? 'bg-light-danger text-danger' : 'bg-light-success text-success' }} px-2 py-1">
                                    {{ $item->current_health }}%
                                </span>
                            @elseif($item->condition)
                                <span class="badge {{ strtolower($item->condition) === 'rusak' ? 'bg-light-danger text-danger' : 'bg-light-success text-success' }} px-2 py-1">{{ $item->condition }}</span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                            @if($item->area)<small class="text-muted d-block mt-1">{{ $item->area }}</small>@endif
                        </td>
                        <td class="text-muted py-3">{{ $item->purchase_date?->format('d/m/Y') ?? '-' }}</td>
                    @endif

                    <td class="text-end py-3">
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('inventory.show', $item->id) }}" class="btn btn-sm btn-light-primary rounded-pill px-3">
                                <i class="bi bi-eye"></i>
                            </a>
                            <button data-bs-toggle="modal" data-bs-target="#edit-modal" class="btn btn-sm btn-light-warning rounded-pill px-3" title="Edit"
                                data-action="{{ route('inventory.update', $item->id) }}"
                                data-edit="{{ json_encode(['name' => $item->name, 'device_password' => $item->device_password, 'manufacturer' => $item->manufacturer, 'model' => $item->model, 'keyboard' => $item->keyboard, 'mouse' => $item->mouse, 'user_id' => $item->user_id, 'ip_address' => $item->ip_address, 'color' => $item->color, 'location' => $item->location, 'area' => $item->area, 'ssid' => $item->ssid, 'ssid_password' => $item->ssid_password, 'admin_username' => $item->admin_username, 'condition' => $item->condition, 'accessories' => $item->accessories, 'ram' => $item->ram, 'rom' => $item->rom, 'current_health' => $item->current_health, 'os' => $item->os, 'os_version' => $item->os_version, 'processor' => $item->processor, 'usability_tier' => $item->usability_tier, 'component_type' => $item->component_type, 'size' => $item->size, 'parent_id' => $item->parent_id, 'price' => $item->price,
                                    'purchase_date' => $item->purchase_date?->format('Y-m-d'), 'in_use' => $item->in_use === false ? '0' : '1',
                                    'images' => collect(['image1', 'image2', 'image3'])->filter(fn ($f) => $item->$f)->mapWithKeys(fn ($f) => [$f => asset('storage/' . $item->$f)]),
                                ]) }}">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <form action="{{ route('inventory.destroy', $item->id) }}" method="POST" class="d-inline" onsubmit="return confirmDelete(this, 'Delete item?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-light-danger rounded-pill px-3">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="{{ in_array($category, ['PC', 'Laptop', 'Dashboard']) ? 5 : ($category == 'Phone' ? 9 : ($isNetwork ? ($category == 'Switch' ? 7 : 8) : 8)) }}" class="text-center py-5 text-muted">No records found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
        </div>
    </section>
@endsection

@section('modals')
    @php $attachTargets = \App\Models\Device::whereIn('category', ['PC', 'Laptop', 'Phone'])->get(['id', 'name', 'category']); @endphp
    <div class="modal fade" id="add-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg modal-fullscreen-md-down">
            <form action="{{ route('inventory.store') }}" method="POST" enctype="multipart/form-data" class="w-100 h-100">
                <div class="modal-content border-0 shadow-sm h-100 hr-modal">
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fw-bold">Register New {{ $category }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    
                    @csrf
                    <input type="hidden" name="category" value="{{ $category }}">
                    <input type="hidden" name="device_group" value="{{ in_array($category, ['PC', 'Laptop', 'Phone']) ? 'Device' : 'Peripheral' }}">
                    
                    <div class="modal-body p-4 p-md-5">
                        
                        @if(in_array($category, ['PC', 'Laptop']))
                            <div class="row g-3 mb-4">
                                <div class="col-12 col-md-6">
                                    <label class="form-label text-muted fw-bold small">Asset Name</label>
                                    <input type="text" name="name" class="form-control" required>
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label text-muted fw-bold small">Device Password</label>
                                    <input type="text" name="device_password" class="form-control">
                                </div>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-12 col-md-6">
                                    <label class="form-label text-muted fw-bold small">Manufacturer</label>
                                    <input type="text" name="manufacturer" class="form-control">
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label text-muted fw-bold small">Model</label>
                                    <input type="text" name="model" class="form-control">
                                </div>
                            </div>
                            
                            <div class="row g-3 mb-4">
                                <div class="col-12 col-md-6">
                                    <label class="form-label text-muted fw-bold small">Keyboard</label>
                                    <input type="text" name="keyboard" class="form-control" placeholder="e.g., Logitech K120">
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label text-muted fw-bold small">Mouse</label>
                                    <input type="text" name="mouse" class="form-control" placeholder="e.g., Logitech B100">
                                </div>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-12">
                                    <label class="form-label text-muted fw-bold small">Assigned User</label>
                                    <select name="user_id" class="form-select">
                                        <option value="">-- Unassigned (STOCK) --</option>
                                        @foreach($employees as $emp)
                                            <option value="{{ $emp->id }}">{{ $emp->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-12 col-md-6">
                                    <label class="form-label text-muted fw-bold small">Purchase Date</label>
                                    <input type="date" name="purchase_date" class="form-control">
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label text-muted fw-bold small">Asset Value</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light">Rp.</span>
                                        <input type="text" oninput="formatPrice(this, 'add_price_actual')" class="form-control">
                                        <input type="hidden" name="price" id="add_price_actual">
                                    </div>
                                </div>
                            </div>

                        @elseif($isNetwork)
                            <div class="row g-3 mb-4">
                                <div class="col-12 col-md-6"><label class="form-label text-muted fw-bold small">Asset Name</label><input type="text" name="name" value="" class="form-control" placeholder="e.g. NET-05-AP" required></div>
                                <div class="col-12 col-md-6"><label class="form-label text-muted fw-bold small">IP Address</label><input type="text" name="ip_address" value="" class="form-control font-monospace" placeholder="192.168.1.1"></div>
                            </div>
                            <div class="row g-3 mb-4">
                                <div class="col-12 col-md-4"><label class="form-label text-muted fw-bold small">Manufacturer</label><input type="text" name="manufacturer" value="" class="form-control"></div>
                                <div class="col-12 col-md-4"><label class="form-label text-muted fw-bold small">Model</label><input type="text" name="model" value="" class="form-control"></div>
                                <div class="col-12 col-md-4"><label class="form-label text-muted fw-bold small">Color</label><input type="text" name="color" value="" class="form-control"></div>
                            </div>
                            <div class="row g-3 mb-4">
                                <div class="col-12 col-md-6"><label class="form-label text-muted fw-bold small">Location</label><input type="text" name="location" value="" class="form-control" placeholder="e.g. Lt. 2, Meja IT"></div>
                                <div class="col-12 col-md-6"><label class="form-label text-muted fw-bold small">Area / Division Served</label><input type="text" name="area" value="" class="form-control" placeholder="e.g. All, Design"></div>
                            </div>
                            <div class="row g-3 mb-4">
                                <div class="col-12 col-md-6"><label class="form-label text-muted fw-bold small">Wi-Fi SSID</label><input type="text" name="ssid" value="" class="form-control"></div>
                                <div class="col-12 col-md-6"><label class="form-label text-muted fw-bold small">Wi-Fi Password</label><input type="text" name="ssid_password" value="" class="form-control"></div>
                            </div>
                            <div class="row g-3 mb-4">
                                <div class="col-12 col-md-6"><label class="form-label text-muted fw-bold small">Admin Username</label><input type="text" name="admin_username" value="" class="form-control"></div>
                                <div class="col-12 col-md-6"><label class="form-label text-muted fw-bold small">Admin Password</label><input type="text" name="device_password" value="" class="form-control"></div>
                            </div>
                            <div class="row g-3 mb-4">
                                <div class="col-12 col-md-4">
                                    <label class="form-label text-muted fw-bold small">Status</label>
                                    <select name="in_use" class="form-select">
                                        <option value="1" >In use</option>
                                        <option value="0" >Not in use</option>
                                    </select>
                                </div>
                                <div class="col-12 col-md-4">
                                    <label class="form-label text-muted fw-bold small">Condition</label>
                                    <select name="condition" class="form-select">
                                        <option value="">-</option>
                                        @foreach(['Sangat Baik', 'Baik', 'OK', 'Cukup', 'Tidak diketahui'] as $cond)<option >{{ $cond }}</option>@endforeach
                                    </select>
                                </div>
                                <div class="col-12 col-md-4"><label class="form-label text-muted fw-bold small">Accessories</label><input type="text" name="accessories" value="" class="form-control" placeholder="Unit + Adaptor"></div>
                            </div>
                        @elseif($category == 'Phone')
                            <div class="row g-3 mb-4">
                                <div class="col-12 col-md-6"><label class="form-label text-muted fw-bold small">Asset Name</label><input type="text" name="name" class="form-control" required></div>
                                <div class="col-12 col-md-6"><label class="form-label text-muted fw-bold small">Device Password</label><input type="text" name="device_password" class="form-control"></div>
                            </div>
                            <div class="row g-3 mb-4">
                                <div class="col-12 col-md-6"><label class="form-label text-muted fw-bold small">Manufacturer</label><input type="text" name="manufacturer" class="form-control"></div>
                                <div class="col-12 col-md-6"><label class="form-label text-muted fw-bold small">Model</label><input type="text" name="model" class="form-control"></div>
                            </div>
                            <div class="row g-3 mb-4">
                                <div class="col-12">
                                    <label class="form-label text-muted fw-bold small">Assigned User</label>
                                    <select name="user_id" class="form-select">
                                        <option value="">-- Unassigned (STOCK) --</option>
                                        @foreach($employees as $emp) <option value="{{ $emp->id }}">{{ $emp->name }}</option> @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="row g-3 mb-4">
                                <div class="col-12 col-md-4"><label class="form-label text-muted fw-bold small">RAM</label><input type="text" name="ram" class="form-control"></div>
                                <div class="col-12 col-md-4"><label class="form-label text-muted fw-bold small">ROM</label><input type="text" name="rom" class="form-control"></div>
                                <div class="col-12 col-md-4"><label class="form-label text-muted fw-bold small">Battery Health %</label><input type="number" name="current_health" min="0" max="100" class="form-control"></div>
                            </div>
                            <div class="row g-3 mb-4">
                                <div class="col-12 col-md-4">
                                    <label class="form-label text-muted fw-bold small">OS</label>
                                    <select name="os" class="form-select"><option value="">-</option><option>IOS</option><option>Android</option></select>
                                </div>
                                <div class="col-12 col-md-4"><label class="form-label text-muted fw-bold small">OS Version</label><input type="text" name="os_version" class="form-control" placeholder="e.g. 18.7.2"></div>
                                <div class="col-12 col-md-4"><label class="form-label text-muted fw-bold small">Processor</label><input type="text" name="processor" class="form-control" placeholder="e.g. Apple A13 Bionic"></div>
                            </div>
                            <div class="row g-3 mb-4">
                                <div class="col-12 col-md-4"><label class="form-label text-muted fw-bold small">Color</label><input type="text" name="color" class="form-control"></div>
                                <div class="col-12 col-md-4">
                                    <label class="form-label text-muted fw-bold small">Condition</label>
                                    <select name="condition" class="form-select"><option value="">-</option>@foreach(['Sangat Baik', 'Baik', 'OK', 'Cukup', 'Tidak diketahui'] as $cond)<option>{{ $cond }}</option>@endforeach</select>
                                </div>
                                <div class="col-12 col-md-4"><label class="form-label text-muted fw-bold small">Usability Tier (1-10)</label><input type="number" name="usability_tier" min="1" max="10" class="form-control"></div>
                            </div>
                            <div class="row g-3 mb-4">
                                <div class="col-12"><label class="form-label text-muted fw-bold small">Accessories (Kelengkapan)</label><input type="text" name="accessories" class="form-control" placeholder="e.g. Unit, Charger, Case"></div>
                            </div>
                            <div class="row g-3 mb-4">
                                <div class="col-12 col-md-6"><label class="form-label text-muted fw-bold small">Purchase Date</label><input type="date" name="purchase_date" class="form-control"></div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label text-muted fw-bold small">Asset Value</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light">Rp.</span><input type="text" oninput="formatPrice(this, 'add_price_actual')" class="form-control"><input type="hidden" name="price" id="add_price_actual">
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="row g-3 mb-4">
                                <div class="col-12 col-md-6"><label class="form-label text-muted fw-bold small">Asset Name</label><input type="text" name="name" class="form-control" required></div>
                                <div class="col-12 col-md-6"><label class="form-label text-muted fw-bold small">Component Type</label><input type="text" name="component_type" class="form-control"></div>
                            </div>
                            <div class="row g-3 mb-4">
                                <div class="col-12 col-md-6"><label class="form-label text-muted fw-bold small">Manufacturer</label><input type="text" name="manufacturer" class="form-control"></div>
                                <div class="col-12 col-md-6"><label class="form-label text-muted fw-bold small">Model</label><input type="text" name="model" class="form-control"></div>
                            </div>
                            <div class="row g-3 mb-4">
                                <div class="col-12 col-md-6"><label class="form-label text-muted fw-bold small">Size / Specs</label><input type="text" name="size" class="form-control"></div>
                                <div class="col-12 col-md-6"><label class="form-label text-muted fw-bold small">Initial Health %</label><input type="number" name="current_health" value="100" class="form-control"></div>
                            </div>
                            
                            <div class="row g-3 mb-4">
                                <div class="col-12 col-md-6">
                                    <label class="form-label text-muted fw-bold small">Assigned User</label>
                                    <select name="user_id" class="form-select">
                                        <option value="">-- Unassigned (STOCK) --</option>
                                        @foreach($employees as $emp)
                                            <option value="{{ $emp->id }}">{{ $emp->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label text-muted fw-bold small">Attach to Device</label>
                                    <select name="parent_id" class="form-select">
                                        <option value="">-- Unassigned (STOCK) --</option>
                                        @foreach($attachTargets as $targetDevice)
                                            <option value="{{ $targetDevice->id }}">{{ $targetDevice->name }} ({{ $targetDevice->category }})</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-12 col-md-6"><label class="form-label text-muted fw-bold small">Purchase Date</label><input type="date" name="purchase_date" class="form-control"></div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label text-muted fw-bold small">Asset Value</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light">Rp.</span><input type="text" oninput="formatPrice(this, 'add_price_actual')" class="form-control"><input type="hidden" name="price" id="add_price_actual">
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div class="row g-3 border-top pt-3 mt-1">
                            <div class="col-12">
                                <label class="form-label text-muted fw-bold small">Asset Gallery (Max 3 Images - Auto-optimized to WEBP)</label>
                                <input type="file" name="images[]" multiple accept="image/*" class="form-control" onchange="validateFiles(this)">
                            </div>
                        </div>

                    </div>
                    
                    <div class="modal-footer border-0">
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end w-100">
                            <button type="button" class="btn btn-light-secondary order-2 order-md-1" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary px-4 fw-bold shadow-sm order-1 order-md-2">Save Asset</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- One edit modal for every row: filled from the clicked Edit button (see scripts). --}}
    <div class="modal fade" id="edit-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg modal-fullscreen-md-down">
            <form method="POST" id="edit-form" enctype="multipart/form-data" class="w-100 h-100">
                <div class="modal-content border-0 shadow-sm h-100 hr-modal">
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fw-bold">Edit {{ $category }} Details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    
                    @csrf @method('PUT')
                    
                    <div class="modal-body p-4 p-md-5">
                        
                        @if(in_array($category, ['PC', 'Laptop']))
                            <div class="row g-3 mb-4">
                                <div class="col-12 col-md-6">
                                    <label class="form-label text-muted fw-bold small">Asset Name</label>
                                    <input type="text" name="name" class="form-control" required>
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label text-muted fw-bold small">Device Password</label>
                                    <input type="text" name="device_password" class="form-control">
                                </div>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-12 col-md-6">
                                    <label class="form-label text-muted fw-bold small">Manufacturer</label>
                                    <input type="text" name="manufacturer" class="form-control">
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label text-muted fw-bold small">Model</label>
                                    <input type="text" name="model" class="form-control">
                                </div>
                            </div>
                            
                            <div class="row g-3 mb-4">
                                <div class="col-12 col-md-6">
                                    <label class="form-label text-muted fw-bold small">Keyboard</label>
                                    <input type="text" name="keyboard" class="form-control" placeholder="e.g., Logitech K120">
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label text-muted fw-bold small">Mouse</label>
                                    <input type="text" name="mouse" class="form-control" placeholder="e.g., Logitech B100">
                                </div>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-12">
                                    <label class="form-label text-muted fw-bold small">Assigned User</label>
                                    <select name="user_id" class="form-select">
                                        <option value="">-- Unassigned (STOCK) --</option>
                                        @foreach($employees as $emp)
                                            <option value="{{ $emp->id }}">{{ $emp->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-12 col-md-6">
                                    <label class="form-label text-muted fw-bold small">Purchase Date</label>
                                    <input type="date" name="purchase_date" class="form-control">
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label text-muted fw-bold small">Asset Value</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light">Rp.</span>
                                        <input type="text" oninput="formatPrice(this, 'edit_price_actual')" data-price-display class="form-control">
                                        <input type="hidden" name="price" id="edit_price_actual">
                                    </div>
                                </div>
                            </div>

                        @elseif($isNetwork)
                            <div class="row g-3 mb-4">
                                <div class="col-12 col-md-6"><label class="form-label text-muted fw-bold small">Asset Name</label><input type="text" name="name" class="form-control" placeholder="e.g. NET-05-AP" required></div>
                                <div class="col-12 col-md-6"><label class="form-label text-muted fw-bold small">IP Address</label><input type="text" name="ip_address" class="form-control font-monospace" placeholder="192.168.1.1"></div>
                            </div>
                            <div class="row g-3 mb-4">
                                <div class="col-12 col-md-4"><label class="form-label text-muted fw-bold small">Manufacturer</label><input type="text" name="manufacturer" class="form-control"></div>
                                <div class="col-12 col-md-4"><label class="form-label text-muted fw-bold small">Model</label><input type="text" name="model" class="form-control"></div>
                                <div class="col-12 col-md-4"><label class="form-label text-muted fw-bold small">Color</label><input type="text" name="color" class="form-control"></div>
                            </div>
                            <div class="row g-3 mb-4">
                                <div class="col-12 col-md-6"><label class="form-label text-muted fw-bold small">Location</label><input type="text" name="location" class="form-control" placeholder="e.g. Lt. 2, Meja IT"></div>
                                <div class="col-12 col-md-6"><label class="form-label text-muted fw-bold small">Area / Division Served</label><input type="text" name="area" class="form-control" placeholder="e.g. All, Design"></div>
                            </div>
                            <div class="row g-3 mb-4">
                                <div class="col-12 col-md-6"><label class="form-label text-muted fw-bold small">Wi-Fi SSID</label><input type="text" name="ssid" class="form-control"></div>
                                <div class="col-12 col-md-6"><label class="form-label text-muted fw-bold small">Wi-Fi Password</label><input type="text" name="ssid_password" class="form-control"></div>
                            </div>
                            <div class="row g-3 mb-4">
                                <div class="col-12 col-md-6"><label class="form-label text-muted fw-bold small">Admin Username</label><input type="text" name="admin_username" class="form-control"></div>
                                <div class="col-12 col-md-6"><label class="form-label text-muted fw-bold small">Admin Password</label><input type="text" name="device_password" class="form-control"></div>
                            </div>
                            <div class="row g-3 mb-4">
                                <div class="col-12 col-md-4">
                                    <label class="form-label text-muted fw-bold small">Status</label>
                                    <select name="in_use" class="form-select">
                                        <option value="1">In use</option>
                                        <option value="0">Not in use</option>
                                    </select>
                                </div>
                                <div class="col-12 col-md-4">
                                    <label class="form-label text-muted fw-bold small">Condition</label>
                                    <select name="condition" class="form-select">
                                        <option value="">-</option>
                                        @foreach(['Sangat Baik', 'Baik', 'OK', 'Cukup', 'Tidak diketahui'] as $cond)<option>{{ $cond }}</option>@endforeach
                                    </select>
                                </div>
                                <div class="col-12 col-md-4"><label class="form-label text-muted fw-bold small">Accessories</label><input type="text" name="accessories" class="form-control" placeholder="Unit + Adaptor"></div>
                            </div>
                        @elseif($category == 'Phone')
                            <div class="row g-3 mb-4">
                                <div class="col-12 col-md-6"><label class="form-label text-muted fw-bold small">Asset Name</label><input type="text" name="name" class="form-control" required></div>
                                <div class="col-12 col-md-6"><label class="form-label text-muted fw-bold small">Device Password</label><input type="text" name="device_password" class="form-control"></div>
                            </div>
                            <div class="row g-3 mb-4">
                                <div class="col-12 col-md-6"><label class="form-label text-muted fw-bold small">Manufacturer</label><input type="text" name="manufacturer" class="form-control"></div>
                                <div class="col-12 col-md-6"><label class="form-label text-muted fw-bold small">Model</label><input type="text" name="model" class="form-control"></div>
                            </div>
                            <div class="row g-3 mb-4">
                                <div class="col-12">
                                    <label class="form-label text-muted fw-bold small">Assigned User</label>
                                    <select name="user_id" class="form-select">
                                        <option value="">-- Unassigned (STOCK) --</option>
                                        @foreach($employees as $emp) <option value="{{ $emp->id }}">{{ $emp->name }}</option> @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="row g-3 mb-4">
                                <div class="col-12 col-md-4"><label class="form-label text-muted fw-bold small">RAM</label><input type="text" name="ram" class="form-control"></div>
                                <div class="col-12 col-md-4"><label class="form-label text-muted fw-bold small">ROM</label><input type="text" name="rom" class="form-control"></div>
                                <div class="col-12 col-md-4"><label class="form-label text-muted fw-bold small">Battery Health %</label><input type="number" name="current_health" min="0" max="100" class="form-control"></div>
                            </div>
                            <div class="row g-3 mb-4">
                                <div class="col-12 col-md-4">
                                    <label class="form-label text-muted fw-bold small">OS</label>
                                    <select name="os" class="form-select">
                                        <option value="">-</option>
                                        @foreach(['IOS', 'Android'] as $osOption)<option>{{ $osOption }}</option>@endforeach
                                    </select>
                                </div>
                                <div class="col-12 col-md-4"><label class="form-label text-muted fw-bold small">OS Version</label><input type="text" name="os_version" class="form-control"></div>
                                <div class="col-12 col-md-4"><label class="form-label text-muted fw-bold small">Processor</label><input type="text" name="processor" class="form-control"></div>
                            </div>
                            <div class="row g-3 mb-4">
                                <div class="col-12 col-md-4"><label class="form-label text-muted fw-bold small">Color</label><input type="text" name="color" class="form-control"></div>
                                <div class="col-12 col-md-4">
                                    <label class="form-label text-muted fw-bold small">Condition</label>
                                    <select name="condition" class="form-select">
                                        <option value="">-</option>
                                        @foreach(['Sangat Baik', 'Baik', 'OK', 'Cukup', 'Tidak diketahui'] as $cond)<option>{{ $cond }}</option>@endforeach
                                    </select>
                                </div>
                                <div class="col-12 col-md-4"><label class="form-label text-muted fw-bold small">Usability Tier (1-10)</label><input type="number" name="usability_tier" min="1" max="10" class="form-control"></div>
                            </div>
                            <div class="row g-3 mb-4">
                                <div class="col-12"><label class="form-label text-muted fw-bold small">Accessories (Kelengkapan)</label><input type="text" name="accessories" class="form-control"></div>
                            </div>
                            <div class="row g-3 mb-4">
                                <div class="col-12 col-md-6"><label class="form-label text-muted fw-bold small">Purchase Date</label><input type="date" name="purchase_date" class="form-control"></div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label text-muted fw-bold small">Asset Value</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light">Rp.</span><input type="text" oninput="formatPrice(this, 'edit_price_actual')" data-price-display class="form-control"><input type="hidden" name="price" id="edit_price_actual">
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="row g-3 mb-4">
                                <div class="col-12 col-md-6"><label class="form-label text-muted fw-bold small">Asset Name</label><input type="text" name="name" class="form-control" required></div>
                                <div class="col-12 col-md-6"><label class="form-label text-muted fw-bold small">Component Type</label><input type="text" name="component_type" class="form-control"></div>
                            </div>
                            <div class="row g-3 mb-4">
                                <div class="col-12 col-md-6"><label class="form-label text-muted fw-bold small">Manufacturer</label><input type="text" name="manufacturer" class="form-control"></div>
                                <div class="col-12 col-md-6"><label class="form-label text-muted fw-bold small">Model</label><input type="text" name="model" class="form-control"></div>
                            </div>
                            <div class="row g-3 mb-4">
                                <div class="col-12 col-md-6"><label class="form-label text-muted fw-bold small">Size / Specs</label><input type="text" name="size" class="form-control"></div>
                                <div class="col-12 col-md-6"><label class="form-label text-muted fw-bold small">Health %</label><input type="number" name="current_health" class="form-control"></div>
                            </div>
                            
                            <div class="row g-3 mb-4">
                                <div class="col-12 col-md-6">
                                    <label class="form-label text-muted fw-bold small">Assigned User</label>
                                    <select name="user_id" class="form-select">
                                        <option value="">-- Unassigned (STOCK) --</option>
                                        @foreach($employees as $emp)
                                            <option value="{{ $emp->id }}">{{ $emp->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label text-muted fw-bold small">Attach to Device</label>
                                    <select name="parent_id" class="form-select">
                                        <option value="">-- Unassigned (STOCK) --</option>
                                        @foreach($attachTargets as $targetDevice)
                                            <option value="{{ $targetDevice->id }}">{{ $targetDevice->name }} ({{ $targetDevice->category }})</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-12 col-md-6"><label class="form-label text-muted fw-bold small">Purchase Date</label><input type="date" name="purchase_date" class="form-control"></div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label text-muted fw-bold small">Asset Value</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light">Rp.</span><input type="text" oninput="formatPrice(this, 'edit_price_actual')" data-price-display class="form-control"><input type="hidden" name="price" id="edit_price_actual">
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div class="row g-3 border-top pt-3 mt-1 mb-3 d-none" data-edit-gallery>
                            <div class="col-12">
                                <label class="form-label">Current Gallery (Click X to remove)</label>
                                <div class="d-flex gap-3 mt-2" data-edit-gallery-items></div>
                                <div id="hidden-removals-edit"></div>
                            </div>
                        </div>

                        <div class="row g-3 border-top pt-3 mt-1">
                            <div class="col-12">
                                <label class="form-label text-muted fw-bold small">Upload New Images (Max 3 - Auto-optimized to WEBP)</label>
                                <input type="file" name="images[]" multiple accept="image/*" class="form-control" onchange="validateFiles(this)">
                            </div>
                        </div>

                    </div>
                    <div class="modal-footer border-0">
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end w-100">
                            <button type="button" class="btn btn-light-secondary order-2 order-md-1" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary px-4 fw-bold shadow-sm order-1 order-md-2">Save Changes</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@section('scripts')
    <script src="{{ asset('assets/extensions/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/extensions/datatables.net/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('assets/extensions/datatables.net-bs5/js/dataTables.bootstrap5.min.js') }}"></script>
    
    <script>
        // Shared edit modal: load the clicked device's values, price and gallery, and point the form at it.
        document.getElementById('edit-modal').addEventListener('show.bs.modal', function (e) {
            const btn = e.relatedTarget;
            if (!btn?.dataset.edit) return;
            const data = JSON.parse(btn.dataset.edit);
            const images = data.images || {};
            delete data.images;
            const form = document.getElementById('edit-form');
            window.fillForm(form, data, btn.dataset.action);
            form.querySelector('[name="images[]"]').value = '';

            const display = form.querySelector('[data-price-display]');
            if (display) display.value = data.price ? Math.round(Number(data.price)).toLocaleString('en-US') : '';

            const gallery = form.querySelector('[data-edit-gallery]');
            document.getElementById('hidden-removals-edit').innerHTML = '';
            gallery.querySelector('[data-edit-gallery-items]').innerHTML = Object.entries(images).map(([field, url]) => `
                <div class="position-relative" id="preview-edit-${field}">
                    <img src="${url}" class="rounded shadow-sm border" style="width: 75px; height: 75px; object-fit: cover;" alt="">
                    <button type="button" onclick="markImageForDeletion('edit', '${field}')" class="btn btn-danger position-absolute top-0 start-100 translate-middle rounded-circle shadow-sm p-0 d-flex align-items-center justify-content-center" style="width: 22px; height: 22px; margin-left: -5px; margin-top: 5px;">
                        <i class="bi bi-x" style="font-size: 14px; line-height: 1;"></i>
                    </button>
                </div>`).join('');
            gallery.classList.toggle('d-none', Object.keys(images).length === 0);
        });

        $(document).ready(function() {
            // Initialize jQuery DataTables
            if (window.bootstrap) {
                document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));
            }

            $('#table1').DataTable({
                "pageLength": 10,
                "language": window.PORTA_DT_LANGUAGE
            });
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
            const error = document.getElementById('file-error');
            if (input.files.length > 3) {
                if(error) error.classList.remove('d-none');
                else alert("Maximum 3 images allowed!");
                input.value = "";
            } else {
                if(error) error.classList.add('d-none');
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