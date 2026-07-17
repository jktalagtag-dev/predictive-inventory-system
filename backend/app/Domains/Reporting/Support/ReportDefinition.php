<?php

namespace App\Domains\Reporting\Support;

/**
 * Static description of a runnable report: what it's called, who may run
 * it, which filters it accepts, and which export formats it supports.
 * Immutable and code-defined — there is no report_definitions table, so
 * the catalog is deployed with the application rather than edited at
 * runtime (CLAUDE.md section 9, "notifications, analytics ... are
 * projections", applied here to keep report shape under code review).
 */
final class ReportDefinition
{
    /**
     * @param  string[]  $formats
     * @param  array<string, array{type: string, required: bool}>  $filters
     * @param  string[]  $columns
     */
    public function __construct(
        public readonly string $code,
        public readonly string $title,
        public readonly string $description,
        public readonly string $permission,
        public readonly array $formats,
        public readonly array $filters,
        public readonly array $columns,
        public readonly string $dataClassification,
    ) {
    }

    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'title' => $this->title,
            'description' => $this->description,
            'permittedFormats' => $this->formats,
            'filters' => collect($this->filters)->map(fn (array $filter, string $key) => [
                'key' => $key,
                'type' => $filter['type'],
                'required' => $filter['required'],
            ])->values()->all(),
            'columns' => $this->columns,
            'dataClassification' => $this->dataClassification,
            'requiredPermission' => $this->permission,
        ];
    }
}
