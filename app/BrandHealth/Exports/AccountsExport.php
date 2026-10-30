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
 * One row per cycle (not per account) — an account with 4 cycles produces 4
 * rows. Growth Rate is that cycle's own follower growth
 * ((end-start)/start x 100); Views/Reach/Engagement % are that cycle vs the
 * one right before it ON THE SAME PLATFORM (same "vs last cycle" comparison
 * as the Growth modal's Volume panel) — the first cycle of each platform for
 * an account has no prior cycle to compare against, so those columns are
 * blank for it. An account with both Instagram and TikTok cycles gets one row
 * per cycle per platform (never blended), with its own Platform column.
 */
class AccountsExport implements FromCollection, WithHeadings, WithMapping, WithStyles
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
            'Cycle Period',
            'Platform',
            'Growth Rate (%)',
            'Growth Status',
            'Reach Change (%)',
            'Reach Status',
            'Views Change (%)',
            'Views Status',
            'Engagement Change (%)',
            'Engagement Status',
        ];
    }

    public function map($row): array
    {
        return [
            $this->sanitizeForSpreadsheet($row['account_name']),
            $row['cycle_label'],
            $row['platform'] === 'tiktok' ? 'TikTok' : 'Instagram',
            $row['growth_rate'],
            $row['growth_status'] ?? '—',
            $row['reach_rate'] ?? '—',
            $row['reach_status'] ?? '—',
            $row['view_rate'] ?? '—',
            $row['view_status'] ?? '—',
            $row['engagement_rate'] ?? '—',
            $row['engagement_status'] ?? '—',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
