<?php

namespace App\Domains\Reporting\Models;

use App\Domains\Identity\Models\Branch;
use App\Domains\Identity\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A requested report export and its delivery lifecycle
 * (DATABASE_DESIGN.md section 10.4). The generated file itself lives on
 * the configured storage disk, keyed by storage_key; this row is the
 * durable, auditable record of the request and its outcome.
 *
 * @property int $id
 * @property int $requested_by_user_id
 * @property int|null $branch_id
 * @property string $report_code
 * @property string $format
 * @property string $status
 */
class ReportExport extends Model
{
    public const STATUSES = ['queued', 'running', 'completed', 'failed', 'expired'];

    public const FORMATS = ['pdf', 'csv', 'xlsx'];

    protected $table = 'report_exports';

    protected $fillable = [
        'requested_by_user_id', 'branch_id', 'report_code', 'format', 'status',
        'filters_snapshot', 'data_cutoff_at', 'storage_key', 'file_name', 'content_type',
        'file_size_bytes', 'expires_at', 'failure_code', 'requested_at', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'filters_snapshot' => 'array',
            'data_cutoff_at' => 'datetime',
            'file_size_bytes' => 'integer',
            'expires_at' => 'datetime',
            'requested_at' => 'datetime',
            'completed_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }
}
