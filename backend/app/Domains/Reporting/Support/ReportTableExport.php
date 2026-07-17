<?php

namespace App\Domains\Reporting\Support;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

/**
 * A single export definition shared by the CSV and XLSX output formats,
 * so both formats always present the same column set for a given report
 * run (CLAUDE.md section 54, "consistent column definitions").
 */
class ReportTableExport implements FromArray, WithHeadings
{
    /**
     * @param  string[]  $columns
     * @param  array<int, array<string, mixed>>  $rows
     */
    public function __construct(private readonly array $columns, private readonly array $rows)
    {
    }

    public function headings(): array
    {
        return $this->columns;
    }

    public function array(): array
    {
        return array_map(fn (array $row) => array_map(
            fn (string $column) => $this->stringify($row[$column] ?? null),
            $this->columns,
        ), $this->rows);
    }

    private function stringify(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        return (string) $value;
    }
}
