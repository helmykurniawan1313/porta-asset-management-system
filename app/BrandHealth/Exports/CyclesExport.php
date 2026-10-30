<?php

namespace App\BrandHealth\Exports;

use App\BrandHealth\Exports\Concerns\SanitizesForSpreadsheet;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Same filtered/scored dataset as the Cycles PDF export (pdf.cycles-list) —
 * a Summary sheet (count of cycles per Health label) followed by the
 * existing one-row-per-cycle Cycles sheet, as a downloadable spreadsheet.
 */
class CyclesExport implements WithMultipleSheets
{
    /**
     * @param  Collection  $healthBreakdown  [['label' => string, 'count' => int, 'color' => array], ...]
     * @param  array<string, array{bg: string, ink: string}>  $healthColors  label => color, for the per-cycle Health column
     */
    public function __construct(
        private Collection $cycles,
        private ?Collection $healthBreakdown = null,
        private array $healthColors = [],
    ) {
    }

    public function sheets(): array
    {
        $sheets = [];

        if ($this->healthBreakdown !== null) {
            $sheets[] = new CyclesHealthSummarySheet($this->healthBreakdown);
        }

        $sheets[] = new CyclesListSheet($this->cycles, $this->healthColors);

        return $sheets;
    }
}

/**
 * "How many Bagus, how many Kurang..." — one row per Health label with its
 * count across the exported cycle set, ordered best-to-worst. Each row is
 * filled with that label's own tier color (BucketColorResolver), matching
 * the live app's StatusBadge and the PDF export's summary table.
 */
class CyclesHealthSummarySheet implements FromCollection, WithEvents, WithHeadings, WithMapping, WithStyles, WithTitle
{
    public function __construct(private Collection $healthBreakdown)
    {
    }

    public function collection(): Collection
    {
        return $this->healthBreakdown;
    }

    public function headings(): array
    {
        return ['Health Label', 'Cycle Count'];
    }

    public function map($row): array
    {
        return [$row['label'], $row['count']];
    }

    public function title(): string
    {
        return 'Summary';
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
                foreach ($this->healthBreakdown->values() as $index => $row) {
                    $excelRow = $index + 2; // row 1 is the header
                    $argb = 'FF'.$row['color']['bg'];

                    $event->sheet->getStyle("A{$excelRow}:B{$excelRow}")->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setARGB($argb);

                    $event->sheet->getStyle("A{$excelRow}:B{$excelRow}")
                        ->getFont()->getColor()->setARGB('FF'.$row['color']['ink']);
                }
            },
        ];
    }
}

class CyclesListSheet implements FromCollection, WithEvents, WithHeadings, WithMapping, WithStyles, WithTitle
{
    use SanitizesForSpreadsheet;

    /**
     * @param  array<string, array{bg: string, ink: string}>  $healthColors  label => color
     */
    public function __construct(private Collection $cycles, private array $healthColors = [])
    {
    }

    public function collection(): Collection
    {
        return $this->cycles;
    }

    public function headings(): array
    {
        return [
            'Account',
            'Platform',
            'Period Start',
            'Period End',
            'PM Name',
            'Growth Rate (%)',
            'Visibility',
            'Engagement',
            'Health Rate',
            'Health',
        ];
    }

    public function map($cycle): array
    {
        return [
            $this->sanitizeForSpreadsheet($cycle['account']['name'] ?? '—'),
            $cycle['platform'] === 'tiktok' ? 'TikTok' : 'Instagram',
            $cycle['cycle_start_date_formatted'],
            $cycle['cycle_end_date_formatted'],
            $this->sanitizeForSpreadsheet($cycle['project_manager']['name'] ?? '—'),
            $cycle['scores']['growth_rate'],
            $cycle['scores']['visibility_rate'],
            $cycle['scores']['engagement_score'],
            $cycle['scores']['health_rate'],
            $cycle['scores']['health_label'],
        ];
    }

    public function title(): string
    {
        return 'Cycles';
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
                foreach ($this->cycles->values() as $index => $cycle) {
                    $label = $cycle['scores']['health_label'] ?? null;
                    $color = $this->healthColors[$label] ?? null;
                    if (! $color) {
                        continue;
                    }

                    $excelRow = $index + 2; // row 1 is the header, Health is column J

                    $event->sheet->getStyle("J{$excelRow}")->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setARGB('FF'.$color['bg']);

                    $event->sheet->getStyle("J{$excelRow}")
                        ->getFont()->getColor()->setARGB('FF'.$color['ink']);
                }
            },
        ];
    }
}
