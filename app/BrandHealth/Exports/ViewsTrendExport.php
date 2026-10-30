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
 * Same filtered/sorted dataset as the Views Trend page and its PDF export —
 * one row per account+platform, as a downloadable spreadsheet.
 */
class ViewsTrendExport implements FromCollection, WithHeadings, WithMapping, WithStyles
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
            'Trend',
            'Last Cycle Median Views',
            'Prior Cycle Median Views',
            'Max Views',
            'Min Views',
            'Total Reels',
            'Delta (%)',
            'As Of',
        ];
    }

    public function map($row): array
    {
        return [
            $this->sanitizeForSpreadsheet($row['account_name']),
            $row['platform'] === 'tiktok' ? 'TikTok' : 'Instagram',
            ucfirst(str_replace('_', ' ', $row['trend'])),
            $row['last_avg_views'],
            $row['prior_avg_views'],
            $row['last_max_views'],
            $row['last_min_views'],
            $row['last_post_count'] ?? 0,
            $row['delta_pct'],
            $row['last_cycle_label'],
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
