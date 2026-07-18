<?php

namespace App\Domains\Procurement\Models;

use Database\Factories\SupplierFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $code
 * @property string $legal_name
 * @property bool $is_active
 * @property int $row_version
 */
class Supplier extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code', 'legal_name', 'tax_identifier', 'email', 'phone',
        'address_line_1', 'address_line_2', 'city', 'province', 'postal_code',
        'country_code', 'default_currency_code', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'deleted_at' => 'datetime',
            'row_version' => 'integer',
        ];
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(SupplierContact::class);
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    protected static function newFactory(): SupplierFactory
    {
        return SupplierFactory::new();
    }
}
