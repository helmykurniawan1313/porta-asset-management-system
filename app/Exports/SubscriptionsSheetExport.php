<?php

namespace App\Exports;

use App\Models\Subscription;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class SubscriptionsSheetExport implements FromCollection, WithHeadings, WithMapping, WithTitle
{
    public function collection()
    {
        // Load the account relationship so we know which email pays for it
        return Subscription::with('account')->get();
    }

    public function map($subscription): array
    {
        return [
            $subscription->id,
            $subscription->plan, 
            $subscription->account ? $subscription->account->email : 'No Account Linked',
            $subscription->period,
            $subscription->bill,
            $subscription->currency,
            \Carbon\Carbon::parse($subscription->next_payment_date)->format('M d, Y'),
        ];
    }

    public function headings(): array
    {
        return [
            'Sub ID',
            'Plan Name',
            'Master Account',
            'Billing Period',
            'Amount',
            'Currency',
            'Next Payment Date'
        ];
    }

    public function title(): string
    {
        return 'SUBSCRIPTIONS';
    }
}