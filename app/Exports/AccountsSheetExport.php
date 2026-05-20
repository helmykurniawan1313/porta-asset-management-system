<?php

namespace App\Exports;

use App\Models\Account;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class AccountsSheetExport implements FromCollection, WithHeadings, WithMapping, WithTitle
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        // Fetch all accounts and count how many subscriptions are tied to them
        return Account::withCount('subscriptions')->get();
    }

    /**
     * Map the database columns to the Excel columns
     */
    public function map($account): array
    {
        return [
            $account->id,
            $account->email,
            $account->purpose ?? 'N/A',
            $account->subscriptions_count . ' Active Subs',
        ];
    }

    /**
     * Set the Column Headers at the top of the Excel Sheet
     */
    public function headings(): array
    {
        return [
            'Account ID',
            'Master Email',
            'Purpose',
            'Total Subscriptions'
        ];
    }

    /**
     * Name the tab at the bottom of the Excel file
     */
    public function title(): string
    {
        return 'MASTER ACCOUNTS';
    }
}