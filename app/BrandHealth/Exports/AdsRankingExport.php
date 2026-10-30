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
 * Ads Nominal ranking — same rows as the Cycles page's Ads Nominal ranking
 * modal: one row per account per currency (reach/views ads spend and
 * engagement ads spend summed separately across the picked month range, plus
 * their combined total), grouped into one section per currency so amounts in
 * different currencies are never added together or ranked against each other.
 */
class AdsRankingExport implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    use SanitizesForSpreadsheet;

    public function __construct(
        private Collection $rows,
        private string $filterSummary = '',
    ) {
    }

    public function collection(): Collection
    {
        $header = collect([['Filters: '.$this->filterSummary, '', '', '', '', '']]);
        $blank = collect([['', '', '', '', '', '']]);

        $sections = collect();
        $byCurrency = $this->rows->groupBy('currency');

        foreach ($byCurrency as $currency => $currencyRows) {
            $sections->push([$currency, '', '', '', '', '']);
            $sections->push(['Rank', 'Account', 'Reach/Views Ads Spend', 'Engagement Ads Spend', 'Total Ads Spend', 'Cycles']);

            foreach ($currencyRows->values() as $index => $row) {
                $sections->push([
                    $index + 1,
                    $this->sanitizeForSpreadsheet($row['account_name']),
                    round($row['reach_views_ads_spend'], 2),
                    round($row['engagement_ads_spend'], 2),
                    round($row['total_ads_spend'], 2),
                    $row['cycle_count'],
                ]);
            }

            $sections->push(['', '', '', '', '', '']);
        }

        return $header->concat($blank)->concat($sections);
    }

    public function headings(): array
    {
        return [];
    }

    public function map($row): array
    {
        return $row;
    }

    public function styles(Worksheet $sheet): array
    {
        $styles = [];
        $currencyCodes = $this->rows->pluck('currency')->unique();

        foreach ($sheet->getRowIterator() as $row) {
            $value = (string) $sheet->getCell('A'.$row->getRowIndex())->getValue();

            if ($value === 'Rank' || str_starts_with($value, 'Filters:') || $currencyCodes->contains($value)) {
                $styles[$row->getRowIndex()] = ['font' => ['bold' => true]];
            }
        }

        return $styles;
    }
}
