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
 * Same filtered dataset as the Performance PDF export (pdf.performances-list),
 * plus Platform (shown on-screen but missing from that PDF) — one row per
 * performance record, as a downloadable spreadsheet.
 */
class PerformancesExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    use SanitizesForSpreadsheet;

    public function __construct(private Collection $performances)
    {
    }

    public function collection(): Collection
    {
        return $this->performances;
    }

    public function headings(): array
    {
        return [
            'Account',
            'Platform',
            'Post Date',
            'Ads',
            'PM Name',
            'Conceptor Name',
            'Views H+7',
            'Status',
        ];
    }

    public function map($performance): array
    {
        return [
            $this->sanitizeForSpreadsheet($performance['account']['name'] ?? '—'),
            ($performance['platform'] ?? 'instagram') === 'tiktok' ? 'TikTok' : 'Instagram',
            $performance['post_date_formatted'],
            $performance['ads'] === null ? '-' : ($performance['ads'] ? 'Yes' : 'No'),
            $this->sanitizeForSpreadsheet($performance['project_manager']['name'] ?? '—'),
            $this->sanitizeForSpreadsheet($performance['conceptor']['name'] ?? '—'),
            $performance['total_views_h7'],
            $performance['views_status'] ?? '—',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
