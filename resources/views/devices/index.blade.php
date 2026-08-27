<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>IT Inventory | TailAdmin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.css" rel="stylesheet" />
    <style>
        .sidebar-bg { background-color: #1C2434; }
        .sidebar-item-active { background-color: #333A48; border-left: 4px solid #3C50E0; }
    </style>
</head>
<body class="bg-slate-100 font-sans">

<div class="flex h-screen overflow-hidden">
    <aside class="sidebar-bg w-64 flex-shrink-0 flex flex-col transition-all duration-300">
        <div class="p-6 text-white text-xl font-bold tracking-widest uppercase italic">
            PORTA IT
        </div>
        <nav class="flex-1 px-4 space-y-1 overflow-y-auto">
            <a href="{{ route('inventory.index', 'Dashboard') }}" class="flex items-center p-3 text-gray-300 hover:text-white hover:bg-gray-700 rounded {{ $category == 'Dashboard' ? 'sidebar-item-active text-white' : '' }}">
                <span class="ml-3 font-medium">Dashboard</span>
            </a>

            <div class="pt-4 pb-2 text-[10px] font-bold text-gray-500 uppercase tracking-widest px-3">Device</div>
            @foreach(['PC', 'Laptop', 'Phone'] as $nav)
                <a href="{{ route('inventory.index', $nav) }}" class="flex items-center p-3 text-gray-400 hover:text-white rounded {{ $category == $nav ? 'sidebar-item-active text-white' : '' }}">
                    <span class="ml-3">{{ $nav }}</span>
                </a>
            @endforeach

            <div class="pt-4 pb-2 text-[10px] font-bold text-gray-500 uppercase tracking-widest px-3">Peripheral & Component</div>
            @foreach(['SSD', 'HDD', 'RAM', 'PROCESSOR', 'MONITOR', 'CHARGER', 'UPS'] as $nav)
                <a href="{{ route('inventory.index', $nav) }}" class="flex items-center p-3 text-gray-400 hover:text-white rounded {{ $category == $nav ? 'sidebar-item-active text-white' : '' }}">
                    <span class="ml-3">{{ $nav }}</span>
                </a>
            @endforeach
        </nav>
    </aside>

    <div class="flex-1 flex flex-col overflow-hidden">
        <header class="flex items-center justify-between px-8 py-4 bg-white shadow-sm border-b border-gray-200">
            <h1 class="text-xl font-bold text-slate-800">{{ $category }}</h1>
            @if($category !== 'Dashboard')
            <button data-modal-target="add-modal" data-modal-toggle="add-modal" class="bg-[#3C50E0] hover:bg-opacity-90 text-white px-5 py-2 rounded shadow-md text-sm font-medium">
                + Add {{ $category }}
            </button>
            @endif
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto p-8">
            @if(session('success'))
                <div class="p-4 mb-6 text-sm text-green-800 rounded-lg bg-green-50 border border-green-200 shadow-sm">{{ session('success') }}</div>
            @endif

            <div class="card-body">
    <div class="table-responsive">
        <table class="table table-hover align-middle" id="table1">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Manufacture</th>
                    
                    {{-- Dynamic Columns based on Category --}}
                    @if(in_array($category, ['PC', 'Laptop', 'Dashboard']))
                        <th>Model</th>
                        <th>User</th>
                    @elseif($category == 'Phone')
                        <th>Model</th>
                        <th>RAM / ROM</th>
                        <th class="text-center">Health</th>
                        <th>Purchase Date</th>
                    @else
                        <th>Type</th>
                        <th>Model</th>
                        <th>Size</th>
                        <th class="text-center">Health</th>
                        <th>Purchase Date</th>
                    @endif
                    
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $item)
                <tr>
                    <td class="fw-bold">{{ $item->name }}</td>
                    <td class="text-muted">{{ $item->manufacturer ?? '-' }}</td>

                    @if(in_array($category, ['PC', 'Laptop', 'Dashboard']))
                        <td>{{ $item->model ?? '-' }}</td>
                        <td>
                            <span class="badge {{ $item->employee ? 'bg-light-primary text-primary' : 'bg-light-secondary text-secondary' }} px-2 py-1">
                                {{ $item->employee->name ?? 'STOCK' }}
                            </span>
                        </td>

                    @elseif($category == 'Phone')
                        <td class="small">{{ $item->model ?? '-' }}</td>
                        <td class="text-primary fw-bold">{{ $item->ram }} / {{ $item->rom }}</td>
                        <td class="text-center">
                            <span class="badge {{ $item->current_health < 50 ? 'bg-light-danger text-danger' : 'bg-light-success text-success' }}">
                                {{ $item->current_health ?? '-' }}%
                            </span>
                        </td>
                        <td class="text-muted small">{{ $item->purchase_date?->format('d M Y') ?? '-' }}</td>

                    @else
                        <td class="small">{{ $item->component_type ?? '-' }}</td>
                        <td class="small">{{ $item->model ?? '-' }}</td>
                        <td class="fw-bold text-muted">{{ $item->size ?? '-' }}</td>
                        <td class="text-center">
                            <span class="badge {{ $item->current_health < 50 ? 'bg-light-danger text-danger' : 'bg-light-success text-success' }}">
                                {{ $item->current_health ? $item->current_health.'%' : '-' }}
                            </span>
                        </td>
                        <td class="text-muted small">{{ $item->purchase_date?->format('d M Y') ?? '-' }}</td>
                    @endif

                    {{-- Unified Action Buttons --}}
                    <td class="text-end">
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('inventory.show', $item->id) }}" class="btn btn-sm btn-light-primary rounded-pill px-3" title="View">
                                <i class="bi bi-eye"></i>
                            </a>
                            <button data-bs-toggle="modal" data-bs-target="#edit-modal-{{ $item->id }}" class="btn btn-sm btn-light-warning rounded-pill px-3" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <form action="{{ route('inventory.destroy', $item->id) }}" method="POST" class="d-inline" onsubmit="return confirmDelete(this, 'Delete this asset?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-light-danger rounded-pill px-3" title="Delete">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
        </main>
    </div>
</div>

<div id="add-modal" tabindex="-1" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 bg-black bg-opacity-50">
    <div class="relative w-full max-w-md bg-white rounded-lg shadow-xl overflow-hidden">
        <form action="{{ route('inventory.store') }}" method="POST">
            @csrf
            <input type="hidden" name="category" value="{{ $category }}">
            
            <div class="px-6 py-4 border-b bg-gray-50 flex justify-between items-center">
                <h3 class="text-lg font-bold text-slate-800">New {{ $category }}</h3>
                <button type="button" data-modal-hide="add-modal" class="text-gray-400 hover:text-gray-900">&times;</button>
            </div>

            <div class="p-6 space-y-4">
                <div><label class="block text-xs font-bold text-gray-500 uppercase mb-1">Asset Name</label><input type="text" name="name" class="w-full border-gray-300 rounded-md text-sm shadow-sm" required></div>
                <div class="grid grid-cols-2 gap-4">
                    <div><label class="block text-xs font-bold text-gray-500 uppercase mb-1">Manufacturer</label><input type="text" name="manufacturer" class="w-full border-gray-300 rounded-md text-sm shadow-sm"></div>
                    <div><label class="block text-xs font-bold text-gray-500 uppercase mb-1">Model</label><input type="text" name="model" class="w-full border-gray-300 rounded-md text-sm shadow-sm"></div>
                </div>

                @if($category == 'Phone')
                    <div class="grid grid-cols-2 gap-4">
                        <div><label class="block text-xs font-bold text-gray-500 uppercase mb-1">RAM</label><input type="text" name="ram" class="w-full border-gray-300 rounded-md text-sm shadow-sm"></div>
                        <div><label class="block text-xs font-bold text-gray-500 uppercase mb-1">ROM</label><input type="text" name="rom" class="w-full border-gray-300 rounded-md text-sm shadow-sm"></div>
                    </div>
                @elseif(!in_array($category, ['PC', 'Laptop', 'Dashboard']))
                    <div class="grid grid-cols-2 gap-4">
                        <div><label class="block text-xs font-bold text-gray-500 uppercase mb-1">Type</label><input type="text" name="component_type" class="w-full border-gray-300 rounded-md text-sm shadow-sm"></div>
                        <div><label class="block text-xs font-bold text-gray-500 uppercase mb-1">Size / Cap</label><input type="text" name="size" class="w-full border-gray-300 rounded-md text-sm shadow-sm"></div>
                    </div>
                @endif

                <div class="grid grid-cols-2 gap-4">
                    <div><label class="block text-xs font-bold text-gray-500 uppercase mb-1">Health %</label><input type="number" name="current_health" value="100" class="w-full border-gray-300 rounded-md text-sm shadow-sm"></div>
                    <div><label class="block text-xs font-bold text-gray-500 uppercase mb-1">Pur. Date</label><input type="date" name="purchase_date" class="w-full border-gray-300 rounded-md text-sm shadow-sm"></div>
                </div>
            </div>

            <div class="px-6 py-4 bg-gray-50 border-t flex justify-end">
                <button type="submit" class="bg-[#3C50E0] text-white px-6 py-2 rounded-md font-bold text-sm shadow hover:bg-opacity-90 transition">Save Asset</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/flowbite/2.3.0/flowbite.min.js"></script>
</body>
</html>