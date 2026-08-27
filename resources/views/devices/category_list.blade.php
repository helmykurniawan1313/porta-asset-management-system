@extends('layouts.app')

@section('title', strtoupper($categoryName) . ' Inventory | Porta IT')
@section('header_title', strtoupper($categoryName) . ' Inventory')

@section('content')
    <div class="flex justify-between items-end mb-6">
        <p class="text-sm">Managing <span class="text-[#3C50E0] font-bold">{{ count($items) }}</span> assets in this category.</p>
        <button data-modal-target="createModal" data-modal-toggle="createModal" class="bg-[#3C50E0] hover:bg-opacity-90 text-white px-6 py-2.5 rounded-sm shadow-lg text-sm font-bold transition flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4"/></svg>
            Add New {{ $categoryName }}
        </button>
    </div>

    @if(session('success'))
        <div class="p-4 mb-6 text-sm text-green-800 rounded-lg bg-green-50 border border-green-200 shadow-sm">{{ session('success') }}</div>
    @endif

    <div class="bg-white rounded-sm border border-[#E2E8F0] shadow-sm overflow-hidden">
        <table class="w-full text-left">
            <thead class="bg-[#F7F9FC] border-b border-[#E2E8F0]">
                <tr>
                    <th class="px-6 py-4 text-xs font-bold text-[#1C2434] uppercase tracking-wider">Name</th>
                    <th class="px-6 py-4 text-xs font-bold text-[#1C2434] uppercase tracking-wider">Manufacture</th>
                    
                    @if(in_array($categoryName, ['PC', 'Laptop']))
                        <th class="px-6 py-4 text-xs font-bold text-[#1C2434] uppercase tracking-wider">Model</th>
                        <th class="px-6 py-4 text-xs font-bold text-[#1C2434] uppercase tracking-wider">Employee</th>
                    @elseif($categoryName == 'Phone')
                        <th class="px-6 py-4 text-xs font-bold text-[#1C2434] uppercase tracking-wider">Model</th>
                        <th class="px-6 py-4 text-xs font-bold text-[#1C2434] uppercase tracking-wider">Specs</th>
                        <th class="px-6 py-4 text-xs font-bold text-[#1C2434] uppercase tracking-wider text-center">Health</th>
                    @else
                        <th class="px-6 py-4 text-xs font-bold text-[#1C2434] uppercase tracking-wider">Type / Model</th>
                        <th class="px-6 py-4 text-xs font-bold text-[#1C2434] uppercase tracking-wider">Size</th>
                        <th class="px-6 py-4 text-xs font-bold text-[#1C2434] uppercase tracking-wider text-center">Health</th>
                    @endif

                    <th class="px-6 py-4 text-xs font-bold text-[#1C2434] uppercase tracking-wider text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[#E2E8F0]">
                @forelse($items as $item)
                    <tr class="hover:bg-[#F9FAFB] transition">
                        <td class="px-6 py-4 font-bold text-[#1C2434]">{{ $item->name ?? '-' }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $item->manufacturer ?? '-' }}</td>

                        @if(in_array($categoryName, ['PC', 'Laptop']))
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $item->model ?? '-' }}</td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-1 {{ $item->employee ? 'bg-blue-50 text-[#3C50E0]' : 'bg-gray-100 text-gray-500' }} rounded text-[10px] font-bold uppercase border border-blue-100/50">
                                    {{ $item->employee->name ?? 'STOCK' }}
                                </span>
                            </td>
                        @elseif($categoryName == 'Phone')
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $item->model ?? '-' }}</td>
                            <td class="px-6 py-4 text-xs font-medium">{{ $item->ram ?? '-' }} / {{ $item->rom ?? '-' }}</td>
                            <td class="px-6 py-4 text-center">
                                <span class="font-bold {{ ($item->current_health ?? 0) < 50 ? 'text-red-500' : 'text-green-500' }}">
                                    {{ $item->current_health ?? '-' }}%
                                </span>
                            </td>
                        @else
                            <td class="px-6 py-4 text-sm">
                                <span class="text-gray-400 text-[10px] font-bold uppercase block">{{ $item->component_type }}</span>
                                <span class="text-gray-600 font-medium">{{ $item->model ?? '-' }}</span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $item->size ?? '-' }}</td>
                            <td class="px-6 py-4 text-center">
                                <span class="font-bold {{ ($item->current_health ?? 0) < 50 ? 'text-red-500' : 'text-green-500' }}">
                                    {{ $item->current_health ? $item->current_health.'%' : '-' }}
                                </span>
                            </td>
                        @endif

                        <td class="px-6 py-4 text-right">
                            <div class="flex justify-end gap-3">
                                <a href="{{ route('inventory.show', $item->id) }}" class="text-[#3C50E0] font-bold text-xs uppercase hover:underline">View</a>
                                <form action="{{ route('inventory.destroy', $item->id) }}" method="POST" onsubmit="return confirmDelete(this, 'Delete this item?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-500 font-bold text-xs uppercase hover:underline">Del</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center text-gray-400 italic">No {{ $categoryName }} found in inventory.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection

@section('modals')
    <div id="createModal" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-[calc(100%-1rem)] max-h-full">
        <div class="relative p-4 w-full max-w-md max-h-full">
            <form action="{{ route('inventory.store') }}" method="POST" class="bg-white rounded-lg shadow-2xl border border-[#E2E8F0]">
                @csrf
                <input type="hidden" name="category" value="{{ $categoryName }}">
                <input type="hidden" name="device_group" value="{{ in_array($categoryName, ['PC', 'Laptop', 'Phone']) ? 'Device' : 'Peripheral' }}">

                <div class="p-5 border-b flex justify-between items-center">
                    <h3 class="font-bold text-[#1C2434] text-lg">Add New {{ $categoryName }}</h3>
                    <button type="button" class="text-gray-400 hover:text-gray-900 rounded-lg text-lg w-8 h-8 flex justify-center items-center" data-modal-hide="createModal">&times;</button>
                </div>
                
                <div class="p-6 space-y-4">
                    <div>
                        <label class="block mb-1 text-[11px] font-bold uppercase text-gray-500">Asset Name</label>
                        <input type="text" name="name" class="w-full bg-gray-50 border-[#E2E8F0] p-2.5 rounded-sm text-sm" placeholder="e.g. Porta-PC-01">
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block mb-1 text-[11px] font-bold uppercase text-gray-500">Manufacturer</label>
                            <input type="text" name="manufacturer" class="w-full bg-gray-50 border-[#E2E8F0] p-2.5 rounded-sm text-sm" placeholder="MSI, MSI">
                        </div>
                        <div>
                            <label class="block mb-1 text-[11px] font-bold uppercase text-gray-500">Model</label>
                            <input type="text" name="model" class="w-full bg-gray-50 border-[#E2E8F0] p-2.5 rounded-sm text-sm" placeholder="PS42 8RC">
                        </div>
                    </div>

                    @if($categoryName == 'Phone')
                        <div class="grid grid-cols-2 gap-4">
                            <div><label class="block mb-1 text-[11px] font-bold uppercase text-gray-500">RAM</label><input type="text" name="ram" class="w-full bg-gray-50 border-[#E2E8F0] p-2.5 rounded-sm text-sm" placeholder="8 GB"></div>
                            <div><label class="block mb-1 text-[11px] font-bold uppercase text-gray-500">ROM</label><input type="text" name="rom" class="w-full bg-gray-50 border-[#E2E8F0] p-2.5 rounded-sm text-sm" placeholder="128 GB"></div>
                        </div>
                    @endif

                    @if(!in_array($categoryName, ['PC', 'Laptop', 'Phone']))
                        <div class="grid grid-cols-2 gap-4">
                            <div><label class="block mb-1 text-[11px] font-bold uppercase text-gray-500">Type</label><input type="text" name="component_type" class="w-full bg-gray-50 border-[#E2E8F0] p-2.5 rounded-sm text-sm" placeholder="NVME, DDR4"></div>
                            <div><label class="block mb-1 text-[11px] font-bold uppercase text-gray-500">Size</label><input type="text" name="size" class="w-full bg-gray-50 border-[#E2E8F0] p-2.5 rounded-sm text-sm" placeholder="512 GB"></div>
                        </div>
                    @endif

                    @if($categoryName != 'PC' && $categoryName != 'Laptop')
                        <div>
                            <label class="block mb-1 text-[11px] font-bold uppercase text-gray-500">Current Health (%)</label>
                            <input type="number" name="current_health" class="w-full bg-gray-50 border-[#E2E8F0] p-2.5 rounded-sm text-sm" min="0" max="100" value="100">
                        </div>
                    @endif

                    <div>
                        <label class="block mb-1 text-[11px] font-bold uppercase text-gray-500">Purchase Date</label>
                        <input type="date" name="purchase_date" class="w-full bg-gray-50 border-[#E2E8F0] p-2.5 rounded-sm text-sm">
                    </div>
                </div>

                <div class="p-5 border-t bg-[#F7F9FC] flex justify-end">
                    <button type="submit" class="w-full bg-[#3C50E0] text-white font-bold rounded-sm text-sm py-2.5 hover:bg-opacity-90 transition">Save {{ $categoryName }}</button>
                </div>
            </form>
        </div>
    </div>
@endsection