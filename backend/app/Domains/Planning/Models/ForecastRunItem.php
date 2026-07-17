<?php

namespace App\Domains\Planning\Models;

use App\Domains\Catalog\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $forecast_run_id
 * @property int $product_id
 * @property string|null $forecast_quantity
 * @property string $cold_start_status
 */
class ForecastRunItem extends Model
{
    public $timestamps = false;

    public const COLD_START_STATUSES = ['sufficient_history', 'insufficient_history', 'manual_override'];

    protected $fillable = [
        'forecast_run_id', 'product_id', 'product_sku_snapshot', 'product_name_snapshot',
        'history_period_count', 'demand_total', 'forecast_quantity', 'cold_start_status',
        'manual_quantity', 'manual_reason', 'manual_expires_at', 'input_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'history_period_count' => 'integer',
            'demand_total' => 'decimal:4',
            'forecast_quantity' => 'decimal:4',
            'manual_quantity' => 'decimal:4',
            'manual_expires_at' => 'datetime',
            'input_snapshot' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function forecastRun(): BelongsTo
    {
        return $this->belongsTo(ForecastRun::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
