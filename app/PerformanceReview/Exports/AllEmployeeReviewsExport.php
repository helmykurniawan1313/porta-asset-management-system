<?php

namespace App\PerformanceReview\Exports;

use App\Models\User;
use App\PerformanceReview\Services\EmployeeReviewReportBuilder;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class AllEmployeeReviewsExport implements WithMultipleSheets
{
    public function __construct(private readonly EmployeeReviewReportBuilder $reportBuilder)
    {
    }

    public function sheets(): array
    {
        $employees = User::whereNotNull('department_id')->orderBy('name')->get();

        $usedTitles = [];

        return $employees->map(function (User $employee) use (&$usedTitles) {
            $title = $this->uniqueSheetTitle($employee->name, $usedTitles);
            $usedTitles[] = $title;

            return new EmployeeReviewSheetExport($employee, $title, $this->reportBuilder);
        })->all();
    }

    /**
     * Excel sheet titles must be <= 31 chars, unique within the workbook,
     * and free of \ / ? * [ ] : characters.
     */
    private function uniqueSheetTitle(string $name, array $usedTitles): string
    {
        $sanitized = preg_replace('/[\\\\\/\?\*\[\]:]/', ' ', $name);
        $base = mb_substr(trim($sanitized), 0, 31);

        $title = $base;
        $suffix = 2;

        while (in_array($title, $usedTitles, true)) {
            $suffixText = " ({$suffix})";
            $title = mb_substr($base, 0, 31 - mb_strlen($suffixText)) . $suffixText;
            $suffix++;
        }

        return $title;
    }
}
