<?php

namespace App\Exports;

use App\Models\ServiceHistory;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class ServiceLogsSheetExport implements FromCollection, WithHeadings, WithMapping, WithTitle
{
    public function collection()
    {
        // Get all service logs from newest to oldest
        return ServiceHistory::with('device')->orderBy('service_date', 'desc')->get();
    }

    public function map($log): array
    {
        return [
            \Carbon\Carbon::parse($log->service_date)->format('M d, Y'),
            $log->device ? $log->device->name : 'Deleted Device',
            $log->description,
            $log->cost ? '$' . number_format($log->cost, 2) : '$0.00',
        ];
    }

    public function headings(): array
    {
        return ['Service Date', 'Device Serviced', 'Description of Work', 'Cost'];
    }

    public function title(): string
    {
        return 'SERVICE LOGS';
    }
}