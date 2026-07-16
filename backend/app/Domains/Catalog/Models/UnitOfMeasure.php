<?php

namespace App\Domains\Catalog\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $code
 * @property string $name
 * @property string $symbol
 * @property string $dimension
 * @property bool $is_active
 * @property int $row_version
 */
class UnitOfMeasure extends Model
{
    use SoftDeletes;

    protected $table = 'units_of_measure';

    protected $fillable = ['code', 'name', 'symbol', 'dimension', 'is_active'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'deleted_at' => 'datetime',
            'row_version' => 'integer',
        ];
    }
}
