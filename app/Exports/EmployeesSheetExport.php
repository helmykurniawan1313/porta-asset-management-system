<?php

namespace App\Exports;

use App\Models\User;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class EmployeesSheetExport implements FromCollection, WithHeadings, WithMapping, WithTitle
{
    public function collection()
    {
        // Must have the word "return" right here!
        return User::with(['department', 'devices'])->get();
    }

    public function map($employee): array
    {
        return [
            $employee->id,
            $employee->name,
            $employee->email ?? 'N/A',
            $employee->department ? $employee->department->name : 'No Department',
            $employee->devices->count() . ' Hardware Assets',
        ];
    }

    public function headings(): array
    {
        return ['ID', 'Employee Name', 'Email', 'Department', 'Hardware Assigned'];
    }

    public function title(): string
    {
        return 'EMPLOYEES';
    }
}