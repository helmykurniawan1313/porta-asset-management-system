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
        
        /* Subtle row highlight */
        .table-hover tbody tr:hover { background-color: rgba(67, 94, 190, 0.04) !important; }
    </style>
 
@endsection

@section('content')

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
                    <a href="{{ route('inventory.export', $category) }}" class="btn btn-light-success btn-sm px-4 fw-bold shadow-sm">
                        <i class="bi bi-file-earmark-excel me-1"></i> EXPORT EXCEL
                    </a>

                    @if($category !== 'Dashboard')
                    <button data-bs-toggle="modal" data-bs-target="#add-modal" class="btn btn-primary btn-sm px-4 fw-bold shadow-sm">
                        <i class="bi bi-plus-lg me-1"></i> NEW {{ strtoupper($category) }}
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
                    <th>MANUFACTURE</th>
                    <th>MODEL</th>
                    
                    @if(in_array($category, ['PC', 'Laptop', 'Dashboard']))
                        <th>USER</th>
                    @elseif($category == 'Phone')
                        <th>RAM / ROM</th>
                        <th class="text-center">HEALTH</th>
                        <th>USER</th>
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
                    <td class=" py-3">{{ $item->name }}</td>
                    <td class="text-muted py-3">{{ $item->manufacturer ?? '-' }}</td>
                    <td class="py-3">{{ $item->model ?? '-' }}</td>

                    @if(in_array($category, ['PC', 'Laptop', 'Dashboard']))
                        <td class="py-3">
                            <span class=" px-2 py-1">
                                {{ $item->user->name ?? 'STOCK' }}
                            </span>
                        </td>
                    @elseif($category == 'Phone')
                        <td class="text-primary fw-semibold py-3">{{ $item->ram ?? '-' }} / {{ $item->rom ?? '-' }}</td>
                        <td class="text-center py-3">
                            <span class="badge {{ $item->current_health < 80 ? 'bg-light-danger text-danger' : 'bg-light-success text-success' }} px-2 py-1">
                                {{ $item->current_health }}%
                            </span>
                        </td>
                        <td class="py-3">
                            <span class=" px-2 py-1">
                                {{ $item->user->name ?? 'STOCK' }}
                            </span>
                        </td>
                    @else
                        <td class="text-primary fw-semibold py-3">{{ $item->component_type ?? '-' }}</td>
                        <td class="text-muted py-3">{{ $item->size ?? '-' }}</td>
                        <td class="text-center py-3">
                            <span class="badge {{ $item->current_health < 50 ? 'bg-light-danger text-danger' : 'bg-light-success text-success' }} px-2 py-1">
                                {{ $item->current_health }}%
                            </span>
                        </td>
                        <td class="text-muted py-3">{{ $item->purchase_date?->format('d/m/Y') ?? '-' }}</td>
                    @endif

                    <td class="text-end py-3">
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('inventory.show', $item->id) }}" class="btn btn-sm btn-light-primary rounded-pill px-3">
                                <i class="bi bi-eye"></i>
                            </a>
                            <button data-bs-toggle="modal" data-bs-target="#edit-modal-{{ $item->id }}" class="btn btn-sm btn-light-warning rounded-pill px-3">
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
                <tr><td colspan="{{ in_array($category, ['PC', 'Laptop', 'Dashboard']) ? 5 : ($category == 'Phone' ? 6 : 8) }}" class="text-center py-5 text-muted">No records found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
        </div>
    </section>
@endsection

@section('modals')
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
                                        @foreach(\App\Models\User::all() as $emp)
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
                                        @foreach(\App\Models\User::all() as $emp) <option value="{{ $emp->id }}">{{ $emp->name }}</option> @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="row g-3 mb-4">
                                <div class="col-12 col-md-4"><label class="form-label text-muted fw-bold small">RAM</label><input type="text" name="ram" class="form-control"></div>
                                <div class="col-12 col-md-4"><label class="form-label text-muted fw-bold small">ROM</label><input type="text" name="rom" class="form-control"></div>
                                <div class="col-12 col-md-4"><label class="form-label text-muted fw-bold small">Health %</label><input type="number" name="current_health" value="100" class="form-control"></div>
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
                                        @foreach(\App\Models\User::all() as $emp)
                                            <option value="{{ $emp->id }}">{{ $emp->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label text-muted fw-bold small">Attach to Device</label>
                                    <select name="parent_id" class="form-select">
                                        <option value="">-- Unassigned (STOCK) --</option>
                                        @foreach(\App\Models\Device::whereIn('category', ['PC', 'Laptop', 'Phone'])->get() as $targetDevice)
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

    @foreach($items as $item)
    <div class="modal fade" id="edit-modal-{{ $item->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg modal-fullscreen-md-down">
            <form action="{{ route('inventory.update', $item->id) }}" method="POST" enctype="multipart/form-data" class="w-100 h-100">
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
                                    <input type="text" name="name" value="{{ $item->name }}" class="form-control" required>
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label text-muted fw-bold small">Device Password</label>
                                    <input type="text" name="device_password" value="{{ $item->device_password }}" class="form-control">
                                </div>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-12 col-md-6">
                                    <label class="form-label text-muted fw-bold small">Manufacturer</label>
                                    <input type="text" name="manufacturer" value="{{ $item->manufacturer }}" class="form-control">
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label text-muted fw-bold small">Model</label>
                                    <input type="text" name="model" value="{{ $item->model }}" class="form-control">
                                </div>
                            </div>
                            
                            <div class="row g-3 mb-4">
                                <div class="col-12 col-md-6">
                                    <label class="form-label text-muted fw-bold small">Keyboard</label>
                                    <input type="text" name="keyboard" value="{{ $item->keyboard ?? '' }}" class="form-control" placeholder="e.g., Logitech K120">
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label text-muted fw-bold small">Mouse</label>
                                    <input type="text" name="mouse" value="{{ $item->mouse ?? '' }}" class="form-control" placeholder="e.g., Logitech B100">
                                </div>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-12">
                                    <label class="form-label text-muted fw-bold small">Assigned User</label>
                                    <select name="user_id" class="form-select">
                                        <option value="">-- Unassigned (STOCK) --</option>
                                        @foreach(\App\Models\User::all() as $emp)
                                            <option value="{{ $emp->id }}" {{ $item->user_id == $emp->id ? 'selected' : '' }}>{{ $emp->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-12 col-md-6">
                                    <label class="form-label text-muted fw-bold small">Purchase Date</label>
                                    <input type="date" name="purchase_date" value="{{ $item->purchase_date ? $item->purchase_date->format('Y-m-d') : '' }}" class="form-control">
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label text-muted fw-bold small">Asset Value</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light">Rp.</span>
                                        <input type="text" value="{{ number_format($item->price, 0) }}" oninput="formatPrice(this, 'edit_price_actual_{{ $item->id }}')" class="form-control">
                                        <input type="hidden" name="price" id="edit_price_actual_{{ $item->id }}" value="{{ $item->price }}">
                                    </div>
                                </div>
                            </div>

                        @elseif($category == 'Phone')
                            <div class="row g-3 mb-4">
                                <div class="col-12 col-md-6"><label class="form-label text-muted fw-bold small">Asset Name</label><input type="text" name="name" value="{{ $item->name }}" class="form-control" required></div>
                                <div class="col-12 col-md-6"><label class="form-label text-muted fw-bold small">Device Password</label><input type="text" name="device_password" value="{{ $item->device_password }}" class="form-control"></div>
                            </div>
                            <div class="row g-3 mb-4">
                                <div class="col-12 col-md-6"><label class="form-label text-muted fw-bold small">Manufacturer</label><input type="text" name="manufacturer" value="{{ $item->manufacturer }}" class="form-control"></div>
                                <div class="col-12 col-md-6"><label class="form-label text-muted fw-bold small">Model</label><input type="text" name="model" value="{{ $item->model }}" class="form-control"></div>
                            </div>
                            <div class="row g-3 mb-4">
                                <div class="col-12">
                                    <label class="form-label text-muted fw-bold small">Assigned User</label>
                                    <select name="user_id" class="form-select">
                                        <option value="">-- Unassigned (STOCK) --</option>
                                        @foreach(\App\Models\User::all() as $emp) <option value="{{ $emp->id }}" {{ $item->user_id == $emp->id ? 'selected' : '' }}>{{ $emp->name }}</option> @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="row g-3 mb-4">
                                <div class="col-12 col-md-4"><label class="form-label text-muted fw-bold small">RAM</label><input type="text" name="ram" value="{{ $item->ram }}" class="form-control"></div>
                                <div class="col-12 col-md-4"><label class="form-label text-muted fw-bold small">ROM</label><input type="text" name="rom" value="{{ $item->rom }}" class="form-control"></div>
                                <div class="col-12 col-md-4"><label class="form-label text-muted fw-bold small">Health %</label><input type="number" name="current_health" value="{{ $item->current_health }}" class="form-control"></div>
                            </div>
                            <div class="row g-3 mb-4">
                                <div class="col-12 col-md-6"><label class="form-label text-muted fw-bold small">Purchase Date</label><input type="date" name="purchase_date" value="{{ $item->purchase_date ? $item->purchase_date->format('Y-m-d') : '' }}" class="form-control"></div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label text-muted fw-bold small">Asset Value</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light">Rp.</span><input type="text" value="{{ number_format($item->price, 0) }}" oninput="formatPrice(this, 'edit_price_actual_{{ $item->id }}')" class="form-control"><input type="hidden" name="price" id="edit_price_actual_{{ $item->id }}" value="{{ $item->price }}">
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="row g-3 mb-4">
                                <div class="col-12 col-md-6"><label class="form-label text-muted fw-bold small">Asset Name</label><input type="text" name="name" value="{{ $item->name }}" class="form-control" required></div>
                                <div class="col-12 col-md-6"><label class="form-label text-muted fw-bold small">Component Type</label><input type="text" name="component_type" value="{{ $item->component_type }}" class="form-control"></div>
                            </div>
                            <div class="row g-3 mb-4">
                                <div class="col-12 col-md-6"><label class="form-label text-muted fw-bold small">Manufacturer</label><input type="text" name="manufacturer" value="{{ $item->manufacturer }}" class="form-control"></div>
                                <div class="col-12 col-md-6"><label class="form-label text-muted fw-bold small">Model</label><input type="text" name="model" value="{{ $item->model }}" class="form-control"></div>
                            </div>
                            <div class="row g-3 mb-4">
                                <div class="col-12 col-md-6"><label class="form-label text-muted fw-bold small">Size / Specs</label><input type="text" name="size" value="{{ $item->size }}" class="form-control"></div>
                                <div class="col-12 col-md-6"><label class="form-label text-muted fw-bold small">Health %</label><input type="number" name="current_health" value="{{ $item->current_health }}" class="form-control"></div>
                            </div>
                            
                            <div class="row g-3 mb-4">
                                <div class="col-12 col-md-6">
                                    <label class="form-label text-muted fw-bold small">Assigned User</label>
                                    <select name="user_id" class="form-select">
                                        <option value="">-- Unassigned (STOCK) --</option>
                                        @foreach(\App\Models\User::all() as $emp)
                                            <option value="{{ $emp->id }}" {{ $item->user_id == $emp->id ? 'selected' : '' }}>{{ $emp->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label text-muted fw-bold small">Attach to Device</label>
                                    <select name="parent_id" class="form-select">
                                        <option value="">-- Unassigned (STOCK) --</option>
                                        @foreach(\App\Models\Device::whereIn('category', ['PC', 'Laptop', 'Phone'])->get() as $targetDevice)
                                            <option value="{{ $targetDevice->id }}" {{ $item->parent_id == $targetDevice->id ? 'selected' : '' }}>{{ $targetDevice->name }} ({{ $targetDevice->category }})</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="row g-3 mb-4">
                                <div class="col-12 col-md-6"><label class="form-label text-muted fw-bold small">Purchase Date</label><input type="date" name="purchase_date" value="{{ $item->purchase_date ? $item->purchase_date->format('Y-m-d') : '' }}" class="form-control"></div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label text-muted fw-bold small">Asset Value</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light">Rp.</span><input type="text" value="{{ number_format($item->price, 0) }}" oninput="formatPrice(this, 'edit_price_actual_{{ $item->id }}')" class="form-control"><input type="hidden" name="price" id="edit_price_actual_{{ $item->id }}" value="{{ $item->price }}">
                                    </div>
                                </div>
                            </div>
                        @endif

                        @if($item->image1 || $item->image2 || $item->image3)
                        <div class="row g-3 border-top pt-3 mt-1 mb-3">
                            <div class="col-12">
                                <label class="form-label text-muted fw-bold small">Current Gallery (Click X to remove)</label>
                                <div class="d-flex gap-3 mt-2">
                                    @foreach(['image1', 'image2', 'image3'] as $imgField)
                                        @if($item->$imgField)
                                            <div class="position-relative" id="preview-{{ $item->id }}-{{ $imgField }}">
                                                <img src="{{ asset('storage/' . $item->$imgField) }}" class="rounded shadow-sm border" style="width: 75px; height: 75px; object-fit: cover;">
                                                
                                                <button type="button" onclick="markImageForDeletion('{{ $item->id }}', '{{ $imgField }}')" class="btn btn-danger position-absolute top-0 start-100 translate-middle rounded-circle shadow-sm p-0 d-flex align-items-center justify-content-center" style="width: 22px; height: 22px; margin-left: -5px; margin-top: 5px;">
                                                    <i class="bi bi-x" style="font-size: 14px; line-height: 1;"></i>
                                                </button>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                                <div id="hidden-removals-{{ $item->id }}"></div>
                            </div>
                        </div>
                        @endif

                        <div class="row g-3 @if(!$item->image1 && !$item->image2 && !$item->image3) border-top pt-3 mt-1 @endif">
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
    @endforeach
@endsection

@section('scripts')
    <script src="{{ asset('assets/extensions/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('assets/extensions/datatables.net/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('assets/extensions/datatables.net-bs5/js/dataTables.bootstrap5.min.js') }}"></script>
    
    <script>
        $(document).ready(function() {
            // Initialize jQuery DataTables
            $('#table1').DataTable({
                "pageLength": 10,
                "language": {
                    "search": "Filter records:",
                    "lengthMenu": "Display _MENU_ entries per page"
                }
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