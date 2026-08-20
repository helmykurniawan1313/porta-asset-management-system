<?php

namespace App\PerformanceReview\Exports;

use App\Models\User;
use App\PerformanceReview\Services\EmployeeReviewReportBuilder;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * One sheet per employee, laid out to match the org's existing review
 * template: Self Review as plain text Q&A, Peer Review as an IMPACT rating
 * matrix (3 peers + HOD column + average) with two trailing text rows, and
 * "Khusus HOD & Accounts" as the upward-review rating matrix (dynamic
 * reviewer columns) with one combined trailing text row.
 */
class EmployeeReviewSheetExport implements WithEvents, WithTitle
{
    private const HEADER_FILL_SELF = 'FCE4B6';
    private const HEADER_FILL_PEER = 'D5E8D4';
    private const HEADER_FILL_HOD = 'DAE8FC';
    private const GREY_FILL = 'BFBFBF';
    private const BORDER_COLOR = '000000';

    public function __construct(
        private readonly User $employee,
        private readonly string $sheetTitle,
        private readonly EmployeeReviewReportBuilder $reportBuilder,
    ) {
    }

    public function title(): string
    {
        return $this->sheetTitle;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $this->render($event->sheet->getDelegate());
            },
        ];
    }

    private function render(Worksheet $sheet): void
    {
        // This export's layout is hand-built around one section per review
        // type (fixed criteria label lists, fixed column counts matching one
        // specific template's questions). If a review type is ever run
        // again under a second template, keyBy('type') keeps only the last
        // one found — the Excel export would need a real rewrite to render
        // multiple templates of the same type; the on-screen report and PDF
        // already handle that case correctly via EmployeeReviewReportBuilder.
        $sections = $this->reportBuilder->build($this->employee)->keyBy('type');
        $row = 1;

        $sheet->setCellValue("A{$row}", 'Nama Karyawan:');
        $sheet->setCellValue("B{$row}", $this->employee->name);
        $sheet->getStyle("A{$row}")->getFont()->setBold(true);
        $row += 2;

        $row = $this->renderSelfReview($sheet, $sections->get('self'), $row);
        $row += 1;
        $row = $this->renderPeerReview($sheet, $sections->get('peer'), $row);
        $row += 1;
        $this->renderUpwardReview($sheet, $sections->get('upward'), $row);

        $sheet->getColumnDimension('A')->setWidth(32);
        foreach (range('B', 'J') as $col) {
            $sheet->getColumnDimension($col)->setWidth(20);
        }
    }

    private function renderSelfReview(Worksheet $sheet, ?array $section, int $row): int
    {
        $startRow = $row;
        $sheet->mergeCells("A{$row}:B{$row}");
        $sheet->setCellValue("A{$row}", 'Self Review');
        $row++;

        $answersByQuestion = collect($section['textAnswers'] ?? [])
            ->mapWithKeys(fn ($q) => [$q['question'] => $q['answers'][0]['answer'] ?? null]);

        // The template's four fixed self-review labels, in the order the
        // underlying questions were authored for the Self Review cycle.
        $questionTexts = $answersByQuestion->keys()->values();
        $labels = ['Apresiasi', 'Goal', 'Input untuk Divisi', 'Input untuk Manajemen'];

        foreach ($labels as $i => $label) {
            $answer = $questionTexts->has($i) ? $answersByQuestion->get($questionTexts[$i]) : null;
            $sheet->setCellValue("A{$row}", $label);
            $sheet->mergeCells("B{$row}:D{$row}");
            $sheet->setCellValue("B{$row}", $answer);
            $sheet->getStyle("A{$row}")->getFont()->setBold(true);
            $row++;
        }

        $this->applyBandFill($sheet, "A{$startRow}:D{$startRow}", self::HEADER_FILL_SELF, true, 'center');
        $this->applyBorders($sheet, "A{$startRow}:D" . ($row - 1));

        return $row;
    }

    private function renderPeerReview(Worksheet $sheet, ?array $section, int $row): int
    {
        $reviewers = collect($section['reviewers'] ?? []);
        $headId = $this->employee->department?->head_of_department_id;
        $hodReviewer = $reviewers->firstWhere('id', $headId);
        $peerReviewers = $reviewers->reject(fn ($r) => $r['id'] === $headId)->values();

        $peerCount = max(3, $peerReviewers->count());

        $startRow = $row;

        // Grey corner cell + "Peer Review" banner across the reviewer columns.
        $sheet->mergeCells("A{$row}:A" . ($row + 1));
        $this->fillCell($sheet, "A{$row}", self::GREY_FILL);
        $peerHeaderStart = 'B';
        $peerHeaderEnd = Coordinate::stringFromColumnIndex(1 + $peerCount);
        $sheet->mergeCells("{$peerHeaderStart}{$row}:{$peerHeaderEnd}{$row}");
        $sheet->setCellValue("{$peerHeaderStart}{$row}", 'Peer Review');
        $this->applyBandFill($sheet, "{$peerHeaderStart}{$row}:{$peerHeaderEnd}{$row}", self::HEADER_FILL_PEER, true, 'center');

        $hodCol = Coordinate::stringFromColumnIndex(2 + $peerCount);
        $avgCol = Coordinate::stringFromColumnIndex(3 + $peerCount);
        $sheet->mergeCells("{$hodCol}{$row}:{$hodCol}" . ($row + 1));
        $sheet->setCellValue("{$hodCol}{$row}", 'HOD');
        $this->applyBandFill($sheet, "{$hodCol}{$row}:{$hodCol}{$row}", self::HEADER_FILL_HOD, true, 'center');
        $sheet->mergeCells("{$avgCol}{$row}:{$avgCol}" . ($row + 1));
        $sheet->setCellValue("{$avgCol}{$row}", 'Rata rata');
        $sheet->getStyle("{$avgCol}{$row}")->getFont()->setBold(true);
        $sheet->getStyle("{$avgCol}{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $row++;

        // Column sub-headers: Nama Orang 1, 2, 3...
        for ($i = 0; $i < $peerCount; $i++) {
            $col = Coordinate::stringFromColumnIndex(2 + $i);
            $name = $peerReviewers->get($i)['name'] ?? null;
            $sheet->setCellValue("{$col}{$row}", $name ? "{$name}" : "Nama Orang " . ($i + 1) . ':');
            $sheet->getStyle("{$col}{$row}")->getFont()->setBold(true);
            $sheet->getStyle("{$col}{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }
        $row++;

        $ratingRows = collect($section['ratingRows'] ?? []);
        $criteriaLabels = ['Invest in Relationship', 'Emphaty', 'Pursue Excellence', 'Accountable', 'Can do attitude', 'Teamwork'];

        $impactStartRow = $row;

        foreach ($criteriaLabels as $i => $label) {
            $ratingRow = $ratingRows->get($i);
            $sheet->setCellValue("B{$row}", $label);
            $sheet->getStyle("B{$row}")->getFont()->setBold(true);

            for ($j = 0; $j < $peerCount; $j++) {
                $col = Coordinate::stringFromColumnIndex(2 + $j);
                $reviewer = $peerReviewers->get($j);
                $score = $reviewer ? $this->scoreForReviewer($ratingRow, $reviewer['id']) : null;
                $sheet->setCellValue("{$col}{$row}", $score);
                $sheet->getStyle("{$col}{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }

            $hodScore = $hodReviewer && $ratingRow ? $this->scoreForReviewer($ratingRow, $hodReviewer['id']) : null;
            $sheet->setCellValue("{$hodCol}{$row}", $hodScore);
            $sheet->getStyle("{$hodCol}{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $sheet->setCellValue("{$avgCol}{$row}", $ratingRow['average'] ?? null);
            $sheet->getStyle("{$avgCol}{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $row++;
        }

        $sheet->mergeCells("A{$impactStartRow}:A" . ($row - 1));
        $sheet->setCellValue("A{$impactStartRow}", 'IMPACT');
        $sheet->getStyle("A{$impactStartRow}")->getFont()->setBold(true);
        $sheet->getStyle("A{$impactStartRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);

        // Trailing free-text rows: Kualitas positif / Saran, one answer per peer reviewer.
        $textAnswers = collect($section['textAnswers'] ?? []);
        $textLabels = ['Kualitas positif', 'Saran'];

        foreach ($textLabels as $i => $label) {
            $question = $textAnswers->get($i);
            $answersByReviewer = collect($question['answers'] ?? [])->keyBy('reviewer');

            $sheet->setCellValue("A{$row}", $label);
            $sheet->getStyle("A{$row}")->getFont()->setBold(true);

            for ($j = 0; $j < $peerCount; $j++) {
                $reviewer = $peerReviewers->get($j);
                $col = Coordinate::stringFromColumnIndex(2 + $j);
                $answer = $reviewer ? ($answersByReviewer->get($reviewer['name'])['answer'] ?? null) : null;
                $sheet->setCellValue("{$col}{$row}", $answer);
            }

            $this->fillCell($sheet, "{$avgCol}{$row}", self::GREY_FILL);
            $row++;
        }

        $this->applyBorders($sheet, "A{$startRow}:{$avgCol}" . ($row - 1));

        return $row;
    }

    private function renderUpwardReview(Worksheet $sheet, ?array $section, int $row): int
    {
        $reviewers = collect($section['reviewers'] ?? []);
        $reviewerCount = max(1, $reviewers->count());

        $startRow = $row;

        $sheet->mergeCells("A{$row}:A" . ($row + 1));
        $this->fillCell($sheet, "A{$row}", self::GREY_FILL);

        $headerEnd = Coordinate::stringFromColumnIndex(1 + $reviewerCount);
        $sheet->mergeCells("B{$row}:{$headerEnd}{$row}");
        $sheet->setCellValue("B{$row}", 'Tim Divisi');
        $this->applyBandFill($sheet, "B{$row}:{$headerEnd}{$row}", self::HEADER_FILL_PEER, true, 'center');

        $avgCol = Coordinate::stringFromColumnIndex(2 + $reviewerCount);
        $sheet->mergeCells("{$avgCol}{$row}:{$avgCol}" . ($row + 1));
        $sheet->setCellValue("{$avgCol}{$row}", 'Rata rata');
        $sheet->getStyle("{$avgCol}{$row}")->getFont()->setBold(true);
        $sheet->getStyle("{$avgCol}{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $row++;

        for ($i = 0; $i < $reviewerCount; $i++) {
            $col = Coordinate::stringFromColumnIndex(2 + $i);
            $name = $reviewers->get($i)['name'] ?? null;
            $sheet->setCellValue("{$col}{$row}", $name ?: 'Nama Orang ' . ($i + 1) . ':');
            $sheet->getStyle("{$col}{$row}")->getFont()->setBold(true);
            $sheet->getStyle("{$col}{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        }
        $row++;

        $ratingRows = collect($section['ratingRows'] ?? []);
        $criteriaLabels = [
            'Menetapkan tujuan',
            'Menyelaraskan pekerjaan team dengan tujuan',
            'Komunikasi',
            'Pengambilan keputusan',
            'Problem solving',
            'Delegasi tanggung jawab',
            'Lingkungan yang aman dalam tim',
        ];

        foreach ($criteriaLabels as $i => $label) {
            $ratingRow = $ratingRows->get($i);
            $sheet->setCellValue("A{$row}", $label);
            $sheet->getStyle("A{$row}")->getFont()->setBold(true);

            for ($j = 0; $j < $reviewerCount; $j++) {
                $col = Coordinate::stringFromColumnIndex(2 + $j);
                $reviewer = $reviewers->get($j);
                $score = $reviewer && $ratingRow ? $this->scoreForReviewer($ratingRow, $reviewer['id']) : null;
                $sheet->setCellValue("{$col}{$row}", $score);
                $sheet->getStyle("{$col}{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            }

            $sheet->setCellValue("{$avgCol}{$row}", $ratingRow['average'] ?? null);
            $sheet->getStyle("{$avgCol}{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $row++;
        }

        // Both upward text questions (leadership strength + development
        // suggestion) are combined into this single "Saran" row per reviewer,
        // since the template only allocates one trailing text row here.
        $textAnswers = collect($section['textAnswers'] ?? []);
        $sheet->setCellValue("A{$row}", 'Saran');
        $sheet->getStyle("A{$row}")->getFont()->setBold(true);

        for ($j = 0; $j < $reviewerCount; $j++) {
            $reviewer = $reviewers->get($j);
            $col = Coordinate::stringFromColumnIndex(2 + $j);

            if ($reviewer) {
                $combined = $textAnswers
                    ->map(function ($q) use ($reviewer) {
                        $answer = collect($q['answers'] ?? [])->firstWhere('reviewer', $reviewer['name']);

                        return $answer ? "{$q['question']}: {$answer['answer']}" : null;
                    })
                    ->filter()
                    ->implode("\n");

                $sheet->setCellValue("{$col}{$row}", $combined ?: null);
            }
        }

        $this->fillCell($sheet, "{$avgCol}{$row}", self::GREY_FILL);
        $row++;

        $this->applyBorders($sheet, "A{$startRow}:{$avgCol}" . ($row - 1));

        return $row;
    }

    private function scoreForReviewer(?array $ratingRow, int $reviewerId): mixed
    {
        return $ratingRow['scoresByReviewerId'][$reviewerId] ?? null;
    }

    private function fillCell(Worksheet $sheet, string $range, string $rgb): void
    {
        $sheet->getStyle($range)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($rgb);
    }

    private function applyBandFill(Worksheet $sheet, string $range, string $rgb, bool $bold, string $align): void
    {
        $this->fillCell($sheet, $range, $rgb);
        $sheet->getStyle($range)->getFont()->setBold($bold);
        $sheet->getStyle($range)->getAlignment()->setHorizontal(
            $align === 'center' ? Alignment::HORIZONTAL_CENTER : Alignment::HORIZONTAL_LEFT
        )->setVertical(Alignment::VERTICAL_CENTER);
    }

    private function applyBorders(Worksheet $sheet, string $range): void
    {
        $sheet->getStyle($range)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB(self::BORDER_COLOR);
    }
}
