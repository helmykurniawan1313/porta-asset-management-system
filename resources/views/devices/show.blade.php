@extends('layouts.app')

@section('title', $device->name . ' | Asset Profile')
@section('header_title', 'Asset Details')

@section('content')
    <div class="flex justify-between items-center mb-8">
        <div>
            <nav class="flex mb-2" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-3 text-xs font-bold uppercase tracking-wider">
                    <li class="inline-flex items-center">
                        <a href="{{ route('inventory.index', 'Dashboard') }}" class="text-gray-400 hover:text-[#3C50E0]">Inventory</a>
                    </li>
                    <li>
                        <div class="flex items-center text-gray-400">
                            <svg class="w-3 h-3 mx-1" fill="currentColor" viewBox="0 0 20 20"><path d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"/></svg>
                            <span class="ml-1 text-[#3C50E0]">{{ $device->category }}</span>
                        </div>
                    </li>
                </ol>
            </nav>
            <h2 class="text-3xl font-bold text-[#1C2434]">{{ $device->name }} <span class="text-gray-300 font-light">#{{ $device->serial_number ?? 'No SN' }}</span></h2>
        </div>
        <div class="flex gap-3">
            <button data-modal-target="edit-modal-{{ $device->id }}" data-modal-toggle="edit-modal-{{ $device->id }}" class="bg-orange-500 hover:bg-orange-600 text-white px-5 py-2 rounded-sm text-sm font-bold shadow-sm transition flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                Edit Asset
            </button>
        </div>
    </div>

    @if(session('success'))
        <div class="p-4 mb-6 text-sm text-green-800 rounded-lg bg-green-50 border border-green-200 shadow-sm">{{ session('success') }}</div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div class="lg:col-span-1 space-y-8">
            <div class="bg-white rounded-sm border border-[#E2E8F0] shadow-sm">
                <div class="p-5 border-b border-[#E2E8F0] bg-[#F7F9FC]">
                    <h3 class="font-bold text-[#1C2434] uppercase text-xs tracking-widest">General Information</h3>
                </div>
                <div class="p-6 space-y-6">
                    <div>
                        <p class="text-[10px] font-bold text-gray-400 uppercase mb-1">Manufacturer & Model</p>
                        <p class="text-[#1C2434] font-medium">{{ $device->manufacturer ?? '-' }} — {{ $device->model ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold text-gray-400 uppercase mb-1">Purchase Date & Age</p>
                        <p class="text-[#1C2434] font-medium">
                            {{ $device->purchase_date?->format('d M Y') ?? 'N/A' }} 
                            <span class="ml-2 px-2 py-0.5 bg-gray-100 text-gray-600 rounded text-[10px] font-bold uppercase">{{ $device->age }}</span>
                        </p>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold text-gray-400 uppercase mb-1">Current Assignment</p>
                        <div class="flex items-center gap-2">
                            <div class="w-6 h-6 rounded-full bg-blue-100 flex items-center justify-center text-[#3C50E0] text-[10px] font-bold">
                                {{ strtoupper(substr($device->employee->name ?? 'S', 0, 1)) }}
                            </div>
                            @if($device->employee)
                                <a href="{{ route('employees.show', $device->employee_id) }}" class="text-[#3C50E0] font-bold hover:underline">{{ $device->employee->name }}</a>
                            @else
                                <span class="text-gray-400 font-bold italic">Unassigned (In Stock)</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-sm border border-[#E2E8F0] shadow-sm">
                <div class="p-5 border-b border-[#E2E8F0] bg-[#F7F9FC]">
                    <h3 class="font-bold text-[#1C2434] uppercase text-xs tracking-widest">Technical Specifications</h3>
                </div>
                <div class="p-6">
                    @if($device->specs && count($device->specs) > 0)
                        <div class="space-y-4">
                            @foreach($device->specs as $key => $value)
                                <div class="border-b border-gray-50 pb-2 last:border-0">
                                    <p class="text-[10px] font-bold text-gray-400 uppercase">{{ str_replace('_', ' ', $key) }}</p>
                                    <p class="text-[#1C2434] font-bold">{{ $value }}</p>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-center text-sm text-gray-400 italic py-4">No technical specs recorded.</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="lg:col-span-2 space-y-8">
            <div class="bg-white rounded-sm border border-[#E2E8F0] shadow-sm overflow-hidden">
                <div class="p-5 border-b border-[#E2E8F0] flex justify-between items-center bg-[#F7F9FC]">
                    <h3 class="font-bold text-[#1C2434] uppercase text-xs tracking-widest">Health & Condition History</h3>
                    <button data-modal-target="healthModal" data-modal-toggle="healthModal" class="bg-[#3C50E0] text-white px-3 py-1.5 rounded-sm text-xs font-bold hover:bg-opacity-90 transition">+ Update Health</button>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-50 border-b border-[#E2E8F0]">
                            <tr>
                                <th class="px-6 py-3 font-bold text-[#1C2434] text-xs uppercase">Date</th>
                                <th class="px-6 py-3 font-bold text-[#1C2434] text-xs uppercase">Health Status</th>
                                <th class="px-6 py-3 font-bold text-[#1C2434] text-xs uppercase">Notes</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#E2E8F0]">
                            @forelse($device->healthRecords()->latest()->take(5)->get() as $health)
                                <tr>
                                    <td class="px-6 py-4 text-gray-500 font-medium">{{ $health->created_at->format('d M Y') }}</td>
                                    <td class="px-6 py-4">
                                        <div class="w-full bg-gray-100 rounded-full h-4 max-w-[120px] overflow-hidden">
                                            <div class="h-4 flex items-center justify-center text-[9px] text-white font-bold leading-none {{ $health->health_percentage < 30 ? 'bg-red-500' : ($health->health_percentage < 70 ? 'bg-orange-400' : 'bg-green-500') }}" style="width: {{ $health->health_percentage }}%">
                                                {{ $health->health_percentage }}%
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-gray-500 text-xs italic">{{ $health->notes ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="px-6 py-10 text-center text-gray-400 italic">No health records recorded.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white rounded-sm border border-[#E2E8F0] shadow-sm">
                <div class="p-5 border-b border-[#E2E8F0] flex justify-between items-center bg-[#F7F9FC]">
                    <h3 class="font-bold text-[#1C2434] uppercase text-xs tracking-widest">Service & Maintenance</h3>
                    <button data-modal-target="serviceModal" data-modal-toggle="serviceModal" class="border border-[#3C50E0] text-[#3C50E0] px-3 py-1.5 rounded-sm text-xs font-bold hover:bg-blue-50 transition">+ Record Service</button>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        @forelse($device->serviceHistories()->latest()->get() as $service)
                            <div class="flex justify-between items-start border-b border-gray-50 pb-4 last:border-0 last:pb-0">
                                <div>
                                    <p class="text-sm font-bold text-[#1C2434]">{{ $service->description }}</p>
                                    <p class="text-xs text-gray-400 uppercase font-medium mt-1">{{ $service->service_date?->format('d M Y') }}</p>
                                </div>
                                <span class="bg-gray-50 text-gray-700 px-3 py-1 rounded border border-gray-100 text-xs font-bold">
                                    ${{ number_format($service->cost, 2) }}
                                </span>
                            </div>
                        @empty
                            <p class="text-center text-sm text-gray-400 italic py-2">No maintenance history recorded.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-sm border border-[#E2E8F0] shadow-sm p-6">
                <h3 class="font-bold text-[#1C2434] uppercase text-xs tracking-widest mb-6">Audit Trail (Recent Activity)</h3>
                <div class="relative border-l-2 border-gray-100 ml-3 space-y-8">
                    @foreach($device->activityLogs()->latest()->take(6)->get() as $log)
                        <div class="relative pl-8">
                            <div class="absolute -left-[9px] top-1 w-4 h-4 rounded-full bg-white border-2 border-[#3C50E0]"></div>
                            <div class="text-[10px] font-bold text-gray-400 uppercase tracking-tighter">{{ $log->created_at->diffForHumans() }}</div>
                            <div class="text-sm font-bold text-[#1C2434]">{{ ucfirst($log->action) }}</div>
                            <div class="text-xs text-gray-500 mt-1">{{ $log->description }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endsection

@section('modals')
    <div id="healthModal" tabindex="-1" aria-hidden="true" class="hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-full max-h-full">
        <div class="relative p-4 w-full max-w-md max-h-full">
            <form action="{{ route('devices.health', $device->id) }}" method="POST" class="bg-white rounded-lg shadow-2xl border border-[#E2E8F0]">
                @csrf
                <div class="p-5 border-b flex justify-between items-center">
                    <h3 class="font-bold text-[#1C2434]">Update Health Recap</h3>
                    <button type="button" class="text-gray-400 hover:text-gray-900 rounded-lg text-lg w-8 h-8 flex justify-center items-center" data-modal-hide="healthModal">&times;</button>
                </div>
                <div class="p-6 space-y-4">
                    <div>
                        <label class="block mb-1 text-[11px] font-bold uppercase text-gray-500">Health Percentage (0-100)</label>
                        <input type="number" name="health_percentage" class="w-full bg-gray-50 border-[#E2E8F0] p-2.5 rounded-sm text-sm" required min="0" max="100">
                    </div>
                    <div>
                        <label class="block mb-1 text-[11px] font-bold uppercase text-gray-500">Condition Notes</label>
                        <textarea name="notes" class="w-full bg-gray-50 border-[#E2E8F0] p-2.5 rounded-sm text-sm" rows="3" placeholder="Describe current issues or status..."></textarea>
                    </div>
                </div>
                <div class="p-5 border-t bg-[#F7F9FC] flex justify-end">
                    <button type="submit" class="w-full bg-[#3C50E0] text-white font-bold rounded-sm text-sm py-2.5 hover:bg-opacity-90">Save Health Data</button>
                </div>
            </form>
        </div>
    </div>

    <div id="serviceModal" tabindex="-1" aria-hidden="true" class="hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-full max-h-full">
        <div class="relative p-4 w-full max-w-md max-h-full">
            <form action="{{ route('devices.service', $device->id) }}" method="POST" class="bg-white rounded-lg shadow-2xl border border-[#E2E8F0]">
                @csrf
                <div class="p-5 border-b flex justify-between items-center">
                    <h3 class="font-bold text-[#1C2434]">Record Maintenance</h3>
                    <button type="button" class="text-gray-400 hover:text-gray-900 rounded-lg text-lg w-8 h-8 flex justify-center items-center" data-modal-hide="serviceModal">&times;</button>
                </div>
                <div class="p-6 space-y-4">
                    <div>
                        <label class="block mb-1 text-[11px] font-bold uppercase text-gray-500">Service Date</label>
                        <input type="date" name="service_date" class="w-full bg-gray-50 border-[#E2E8F0] p-2.5 rounded-sm text-sm" value="{{ date('Y-m-d') }}" required>
                    </div>
                    <div>
                        <label class="block mb-1 text-[11px] font-bold uppercase text-gray-500">Service Description</label>
                        <input type="text" name="description" class="w-full bg-gray-50 border-[#E2E8F0] p-2.5 rounded-sm text-sm" placeholder="e.g., Battery Replacement" required>
                    </div>
                    <div>
                        <label class="block mb-1 text-[11px] font-bold uppercase text-gray-500">Cost ($)</label>
                        <input type="number" step="0.01" name="cost" class="w-full bg-gray-50 border-[#E2E8F0] p-2.5 rounded-sm text-sm" placeholder="0.00">
                    </div>
                </div>
                <div class="p-5 border-t bg-[#F7F9FC] flex justify-end">
                    <button type="submit" class="w-full bg-[#3C50E0] text-white font-bold rounded-sm text-sm py-2.5 hover:bg-opacity-90">Log Maintenance</button>
                </div>
            </form>
        </div>
    </div>
    
@endsection