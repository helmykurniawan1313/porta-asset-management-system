@extends('layouts.app')
@section('header_title', 'Account Profile')

@section('content')
<div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 mb-8">
    <div>
        <nav class="flex mb-2" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1 text-[10px] font-bold uppercase tracking-widest text-gray-400">
                <li><a href="{{ route('accounts.index') }}" class="hover:text-[#3C50E0] transition">Master Accounts</a></li>
                <li><span class="mx-1">/</span></li>
                <li class="text-gray-500">{{ $account->email }}</li>
            </ol>
        </nav>
        <h2 class="text-3xl font-extrabold text-[#1C2434] tracking-tight">{{ $account->email }}</h2>
    </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-12 gap-8">
    
    <div class="xl:col-span-4 space-y-6">
        <div class="bg-white rounded-sm border border-[#E2E8F0] shadow-sm overflow-hidden">
            <div class="p-8 text-center border-b border-[#E2E8F0] bg-[#F7F9FC]">
                <div class="w-16 h-16 bg-white border border-[#E2E8F0] rounded-2xl flex items-center justify-center text-[#3C50E0] mx-auto mb-4 shadow-sm">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                </div>
                <h3 class="text-lg font-bold text-[#1C2434] break-all">{{ $account->email }}</h3>
                <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mt-1">{{ $account->purpose ?? 'General Purpose Account' }}</p>
            </div>

            <div class="p-6 space-y-4">
                <h4 class="text-[10px] font-bold text-gray-400 uppercase tracking-widest border-b pb-2">Financial Impact</h4>
                <div class="flex justify-between items-center text-xs">
                    <span class="font-bold text-gray-500 uppercase">Monthly Burn</span>
                    <span class="font-extrabold text-[#1C2434]">${{ number_format($totalMonthly, 2) }}</span>
                </div>
                <div class="flex justify-between items-center text-xs">
                    <span class="font-bold text-gray-500 uppercase">Yearly Commitment</span>
                    <span class="font-extrabold text-[#3C50E0]">${{ number_format($totalYearly, 2) }}</span>
                </div>
                <div class="flex justify-between items-center text-xs border-t border-dashed pt-4">
                    <span class="font-bold text-gray-500 uppercase">Total Subscriptions</span>
                    <span class="font-bold text-[#1C2434]">{{ count($account->subscriptions) }} Apps</span>
                </div>
            </div>
        </div>
    </div>

    <div class="xl:col-span-8">
        <div class="bg-white rounded-sm border border-[#E2E8F0] shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-[#E2E8F0] bg-[#F7F9FC]">
                <h3 class="font-bold text-[#1C2434] uppercase text-xs tracking-widest">Plans tied to this Account</h3>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-gray-50 text-[10px] uppercase font-bold text-gray-400 border-b">
                        <tr>
                            <th class="px-6 py-4">Subscription Plan</th>
                            <th class="px-6 py-4">Billing</th>
                            <th class="px-6 py-4 text-right">Next Payment</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-sm">
                        @forelse($account->subscriptions as $sub)
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4">
                                    <a href="{{ route('subscriptions.show', $sub->id) }}" class="font-bold text-[#3C50E0] hover:underline">{{ $sub->plan }}</a>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="font-bold text-[#1C2434]">{{ number_format($sub->bill, 2) }} {{ $sub->currency }}</div>
                                    <div class="text-[10px] text-gray-400 uppercase font-bold">{{ $sub->period }}</div>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="font-bold text-[#1C2434] text-xs">{{ $sub->next_payment_date->format('M d, Y') }}</div>
                                    <div class="text-[10px] font-bold {{ $sub->days_remaining <= 7 ? 'text-red-500' : 'text-gray-400' }}">
                                        {{ $sub->days_remaining }} days remaining
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-6 py-12 text-center text-gray-400 italic font-medium">
                                    No subscriptions are currently billed to this account.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection