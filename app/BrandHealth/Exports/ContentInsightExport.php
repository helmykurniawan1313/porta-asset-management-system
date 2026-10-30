<?php

namespace App\BrandHealth\Exports;

use App\BrandHealth\Exports\Concerns\SanitizesForSpreadsheet;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Same filtered/sorted rows as the Content Insights page and its PDF export —
 * one row per account, with each content type's Weighted Content Score.
 */
class ContentInsightExport implements FromCollection, WithEvents, WithHeadings, WithMapping, WithStyles
{
    use SanitizesForSpreadsheet;

    // Score cell fill colors — same green/amber/red tiers the on-screen table
    // uses: green for any non-negative score, amber for a mild drop (down to
    // -10%), red only for a severe drop. Excel column letters, not 0-indexed.
    private const SCORE_COLUMNS = ['E' => 'posts', 'F' => 'reels', 'G' => 'story'];
    private const FILL_POSITIVE = 'FFD6F0E3';
    private const FILL_WARN = 'FFFDF1D7';
    private const FILL_NEGATIVE = 'FFFBDEDE';

    public function __construct(private Collection $rows)
    {
    }

    public function collection(): Collection
    {
        return $this->rows;
    }

    private const TREND_LABELS = [
        'up' => 'Trending up',
        'flat' => 'Flat',
        'down' => 'Trending down',
        'no_data' => 'No prior cycle',
    ];

    public function headings(): array
    {
        return [
            'Account',
            'Platform',
            'Project Manager',
            'Trend',
            'Posts Score (%)',
            'Reels Score (%)',
            'Story Score (%)',
            'Cycle',
        ];
    }

    public function map($row): array
    {
        $scoreFor = fn ($type) => collect($row['weightedScores'] ?? [])->firstWhere('key', $type)['score'] ?? null;

        return [
            $this->sanitizeForSpreadsheet($row['current']['account_name']),
            $row['current']['platform'] === 'tiktok' ? 'TikTok' : 'Instagram',
            $this->sanitizeForSpreadsheet($row['current']['project_manager_name'] ?? '—'),
            self::TREND_LABELS[$row['overall_trend']] ?? $row['overall_trend'],
            $scoreFor('posts') !== null ? round($scoreFor('posts'), 1) : 'N/A',
            $scoreFor('reels') !== null ? round($scoreFor('reels'), 1) : 'N/A',
            $scoreFor('story') !== null ? round($scoreFor('story'), 1) : 'N/A',
            $row['current']['cycle_start_date'].' – '.$row['current']['cycle_end_date'],
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $scoreFor = fn ($row, $type) => collect($row['weightedScores'] ?? [])->firstWhere('key', $type)['score'] ?? null;

                foreach ($this->rows->values() as $index => $row) {
                    $excelRow = $index + 2; // row 1 is the header

                    foreach (self::SCORE_COLUMNS as $column => $type) {
                        $score = $scoreFor($row, $type);
                        if ($score === null) {
                            continue;
                        }

                        $color = match (true) {
                            $score >= 0 => self::FILL_POSITIVE,
                            $score > -10 => self::FILL_WARN,
                            default => self::FILL_NEGATIVE,
                        };

                        $event->sheet->getStyle("{$column}{$excelRow}")->getFill()
                            ->setFillType(Fill::FILL_SOLID)
                            ->getStartColor()->setARGB($color);
                    }
                }
            },
        ];
    }
}
