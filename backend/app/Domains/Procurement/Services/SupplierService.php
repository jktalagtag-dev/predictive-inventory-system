<?php

namespace App\Domains\Procurement\Services;

use App\Domains\Identity\Models\User;
use App\Domains\Procurement\Models\Supplier;
use App\Domains\Procurement\Models\SupplierContact;
use Illuminate\Support\Facades\DB;

class SupplierService
{
    public function create(array $attributes, User $actor): Supplier
    {
        return DB::transaction(function () use ($attributes, $actor) {
            $this->assertCodeAvailable($attributes['code']);
            if (! empty($attributes['tax_identifier'])) {
                $this->assertTaxIdentifierAvailable($attributes['tax_identifier']);
            }

            $supplier = Supplier::query()->create([
                ...$attributes,
                'row_version' => 1,
                'created_by_user_id' => $actor->id,
                'updated_by_user_id' => $actor->id,
            ]);

            return $supplier->refresh();
        });
    }

    public function update(Supplier $supplier, array $attributes, User $actor): Supplier
    {
        return DB::transaction(function () use ($supplier, $attributes, $actor) {
            $locked = Supplier::query()->lockForUpdate()->findOrFail($supplier->id);

            if (array_key_exists('code', $attributes) && $attributes['code'] !== $locked->code) {
                $this->assertCodeAvailable($attributes['code']);
            }

            if (! empty($attributes['tax_identifier']) && $attributes['tax_identifier'] !== $locked->tax_identifier) {
                $this->assertTaxIdentifierAvailable($attributes['tax_identifier']);
            }

            $locked->fill($attributes);
            $locked->updated_by_user_id = $actor->id;
            $locked->row_version = $locked->row_version + 1;
            $locked->save();

            return $locked;
        });
    }

    public function createContact(Supplier $supplier, array $attributes, User $actor): SupplierContact
    {
        return DB::transaction(function () use ($supplier, $attributes, $actor) {
            if (! empty($attributes['is_primary'])) {
                $this->clearExistingPrimaryContact($supplier);
            }

            return SupplierContact::query()->create([
                ...$attributes,
                'supplier_id' => $supplier->id,
                'row_version' => 1,
                'created_by_user_id' => $actor->id,
                'updated_by_user_id' => $actor->id,
            ]);
        });
    }

    public function updateContact(SupplierContact $contact, array $attributes, User $actor): SupplierContact
    {
        return DB::transaction(function () use ($contact, $attributes, $actor) {
            $locked = SupplierContact::query()->lockForUpdate()->findOrFail($contact->id);

            if (! empty($attributes['is_primary'])) {
                $this->clearExistingPrimaryContact($locked->supplier, exceptContactId: $locked->id);
            }

            $locked->fill($attributes);
            $locked->updated_by_user_id = $actor->id;
            $locked->row_version = $locked->row_version + 1;
            $locked->save();

            return $locked;
        });
    }

    private function clearExistingPrimaryContact(Supplier $supplier, ?int $exceptContactId = null): void
    {
        SupplierContact::query()
            ->where('supplier_id', $supplier->id)
            ->where('is_primary', true)
            ->when($exceptContactId, fn ($query) => $query->where('id', '!=', $exceptContactId))
            ->update(['is_primary' => false]);
    }

    private function assertCodeAvailable(string $code): void
    {
        if (Supplier::query()->withTrashed()->where('code', $code)->exists()) {
            throw new SupplierException('DUPLICATE_SUPPLIER_CODE', 409, 'A supplier with this code already exists.');
        }
    }

    private function assertTaxIdentifierAvailable(string $taxIdentifier): void
    {
        if (Supplier::query()->withTrashed()->where('tax_identifier', $taxIdentifier)->exists()) {
            throw new SupplierException('DUPLICATE_TAX_IDENTIFIER', 409, 'A supplier with this tax identifier already exists.');
        }
    }
}
