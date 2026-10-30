<?php

namespace App\BrandHealth\Exports\Concerns;

/**
 * Guards against CSV/formula injection: Excel/Sheets treats a cell starting
 * with =, +, -, or @ as a formula, and account/employee names throughout this
 * app are freely user-entered (any editor can rename an account). A name like
 * `=HYPERLINK("http://evil","click")` would otherwise execute as a live
 * formula the moment someone opens the exported spreadsheet. Prefixing with a
 * leading apostrophe forces Excel to treat the cell as literal text — the
 * apostrophe itself never displays, so this has no visible effect on normal
 * values.
 */
trait SanitizesForSpreadsheet
{
    private function sanitizeForSpreadsheet(mixed $value): mixed
    {
        if (! is_string($value) || $value === '') {
            return $value;
        }

        return in_array($value[0], ['=', '+', '-', '@'], true) ? "'{$value}" : $value;
    }
}
