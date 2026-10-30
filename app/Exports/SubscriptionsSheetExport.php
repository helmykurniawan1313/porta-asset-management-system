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
            $subscription->status,
            $subscription->app,
            $subscription->plan,
            $subscription->account ? $subscription->account->email : 'No Account Linked',
            $subscription->login_with_google ? 'Yes' : 'No',
            $subscription->shared_with,
            $subscription->division,
            $subscription->period,
            $subscription->bill,
            $subscription->currency,
            $subscription->next_payment_date?->format('M d, Y'),
            $subscription->card_info,
            $subscription->notes,
        ];
    }

    public function headings(): array
    {
        return [
            'Sub ID',
            'Status',
            'App',
            'Plan Name',
            'Master Account',
            'Login by Google',
            'Shared With',
            'Division',
            'Billing Period',
            'Amount',
            'Currency',
            'Next Payment Date',
            'Card',
            'Note',
        ];
    }

    public function title(): string
    {
        return 'SUBSCRIPTIONS';
    }
}