<?php

namespace App\BrandHealth\Exports;

use App\BrandHealth\Exports\Concerns\SanitizesForSpreadsheet;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Best PM & Best Conceptor leaderboards on one sheet — the same data the
 * ranking modal shows, respecting its platform + month/cycle range filters.
 *
 * Layout:
 *   1. "Filters: …" context row
 *   2. "Grouped by: post month | cycle start month" context row
 *   3. blank
 *   4. column header (Rank / Name / Median Views / Total Views / Min Views / Max Views / Total Reels)
 *   5. "PROJECT MANAGERS" section, then a blank, then "CONCEPTORS" section
 *
 * Each person ranked best-to-worst by median views per post.
 */
class PmConceptorRankingExport implements FromCollection, WithStyles
{
    use SanitizesForSpreadsheet;

    public function __construct(
        private Collection $projectManagers,
        private Collection $conceptors,
        private string $filterSummary = '',
        private string $groupedBy = 'post month',
    ) {
    }

    public function collection(): Collection
    {
        $rows = collect();

        $rows->push(['Filters: '.$this->filterSummary, '', '', '', '', '', '']);
        $rows->push(['Grouped by: '.$this->groupedBy, '', '', '', '', '', '']);
        $rows->push(['', '', '', '', '', '', '']);
        $rows->push(['Rank', 'Name', 'Median Views', 'Total Views', 'Min Views', 'Max Views', 'Total Reels']);

        $rows->push(['PROJECT MANAGERS', '', '', '', '', '', '']);
        $this->appendPeople($rows, $this->projectManagers);

        $rows->push(['', '', '', '', '', '', '']);
        $rows->push(['CONCEPTORS', '', '', '', '', '', '']);
        $this->appendPeople($rows, $this->conceptors);

        return $rows;
    }

    private function appendPeople(Collection $rows, Collection $people): void
    {
        if ($people->isEmpty()) {
            $rows->push(['—', 'No one with recorded views for these filters', '', '', '', '', '']);

            return;
        }

        foreach ($people->values() as $index => $person) {
            $rows->push([
                $index + 1,
                $this->sanitizeForSpreadsheet($person['employee_name']),
                $person['avg_views'],
                $person['total_views'],
                $person['min_views'],
                $person['max_views'],
                $person['post_count'],
            ]);
        }
    }

    public function styles(Worksheet $sheet): array
    {
        // Bold anything that's a header/section title, found by cell value
        // (row positions shift with the PM list length and the two context rows).
        $styles = [];

        foreach ($sheet->getRowIterator() as $row) {
            $value = (string) $sheet->getCell('A'.$row->getRowIndex())->getValue();

            if ($value === 'Rank'
                || $value === 'PROJECT MANAGERS'
                || $value === 'CONCEPTORS'
                || str_starts_with($value, 'Filters:')
                || str_starts_with($value, 'Grouped by:')) {
                $styles[$row->getRowIndex()] = ['font' => ['bold' => true]];
            }
        }

        return $styles;
    }
}
