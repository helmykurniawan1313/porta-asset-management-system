<?php

namespace App\BrandHealth\Exports;

use App\BrandHealth\Exports\Concerns\SanitizesForSpreadsheet;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Same filtered dataset as the Account Performance PDF export
 * (pdf.account-performance-list) — one row per account+platform (its most
 * recent cycle), same columns, just as a downloadable spreadsheet.
 */
class AccountPerformanceExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    use SanitizesForSpreadsheet;

    public function __construct(private Collection $rows)
    {
    }

    public function collection(): Collection
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return [
            'Account',
            'Platform',
            'Latest Cycle',
            'PM Name',
            'Growth Rate (%)',
            'Visibility',
            'Engagement',
            'Health Rate',
            'Health',
        ];
    }

    public function map($row): array
    {
        return [
            $this->sanitizeForSpreadsheet($row['account_name']),
            $row['platform'] === 'tiktok' ? 'TikTok' : 'Instagram',
            $row['latest_cycle_label'],
            $this->sanitizeForSpreadsheet($row['project_manager']['name'] ?? '—'),
            $row['growth_rate'],
            $row['visibility_rate'],
            $row['engagement_score'],
            $row['health_rate'],
            $row['health_label'],
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
