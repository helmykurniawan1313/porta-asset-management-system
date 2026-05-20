@extends('layouts.app')

@section('title', 'Activity Logs | Porta IT')
@section('header_title', 'System Activity Logs')

@section('content')
<div class="bg-white rounded-sm border border-[#E2E8F0] shadow-sm overflow-hidden">
    <table class="w-full text-left text-sm">
        <thead class="bg-[#F7F9FC] border-b border-[#E2E8F0]">
            <tr>
                <th class="px-6 py-4 font-bold text-[#1C2434] uppercase text-xs">Timestamp</th>
                <th class="px-6 py-4 font-bold text-[#1C2434] uppercase text-xs">Asset</th>
                <th class="px-6 py-4 font-bold text-[#1C2434] uppercase text-xs">Action</th>
                <th class="px-6 py-4 font-bold text-[#1C2434] uppercase text-xs">Details</th>
                <th class="px-6 py-4 font-bold text-[#1C2434] uppercase text-xs">Employee Involved</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-[#E2E8F0]">
            @foreach(\App\Models\ActivityLog::with(['device', 'employee'])->latest()->paginate(20) as $log)
            <tr class="hover:bg-gray-50 transition">
                <td class="px-6 py-4 text-gray-400 font-medium">{{ $log->created_at->format('d M Y, H:i') }}</td>
                <td class="px-6 py-4">
                    <a href="{{ route('inventory.show', $log->device_id) }}" class="font-bold text-[#3C50E0] hover:underline">
                        {{ $log->device->name }}
                    </a>
                </td>
                <td class="px-6 py-4">
                    <span class="px-2 py-1 rounded text-[10px] font-bold uppercase 
                        {{ $log->action == 'Transfer' ? 'bg-blue-50 text-blue-600' : '' }}
                        {{ $log->action == 'Attach' ? 'bg-green-50 text-green-600' : '' }}
                        {{ $log->action == 'Detach' ? 'bg-red-50 text-red-600' : '' }}">
                        {{ $log->action }}
                    </span>
                </td>
                <td class="px-6 py-4 text-gray-600 font-medium">{{ $log->description }}</td>
                <td class="px-6 py-4">
                    @if($log->employee)
                        <a href="{{ route('employees.show', $log->employee_id) }}" class="hover:underline">
                            {{ $log->employee->name }}
                        </a>
                    @else
                        <span class="text-gray-300 italic">None</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div class="mt-6">
    {{-- Pagination links will appear here if you use paginate() --}}
</div>
@endsection