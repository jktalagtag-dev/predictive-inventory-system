<?php

namespace App\Domains\Procurement\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $supplier_id
 * @property string $full_name
 * @property bool $is_primary
 * @property bool $is_active
 * @property int $row_version
 */
class SupplierContact extends Model
{
    use SoftDeletes;

    protected $fillable = ['supplier_id', 'full_name', 'job_title', 'email', 'phone', 'is_primary', 'is_active'];

    protected function casts(): array
    {
        return [
            'is_primary' => 'boolean',
            'is_active' => 'boolean',
            'deleted_at' => 'datetime',
            'row_version' => 'integer',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }
}
