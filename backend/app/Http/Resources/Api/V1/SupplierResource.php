<?php

namespace App\Http\Resources\Api\V1;

use App\Domains\Procurement\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property Supplier $resource
 */
class SupplierResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $supplier = $this->resource;

        return [
            'id' => (string) $supplier->id,
            'code' => $supplier->code,
            'legalName' => $supplier->legal_name,
            'taxIdentifier' => $supplier->tax_identifier,
            'email' => $supplier->email,
            'phone' => $supplier->phone,
            'addressLine1' => $supplier->address_line_1,
            'addressLine2' => $supplier->address_line_2,
            'city' => $supplier->city,
            'province' => $supplier->province,
            'postalCode' => $supplier->postal_code,
            'countryCode' => $supplier->country_code,
            'defaultCurrencyCode' => $supplier->default_currency_code,
            'isActive' => (bool) $supplier->is_active,
            'contacts' => $supplier->relationLoaded('contacts') ? $supplier->contacts->map(fn ($contact) => [
                'id' => (string) $contact->id,
                'fullName' => $contact->full_name,
                'jobTitle' => $contact->job_title,
                'email' => $contact->email,
                'phone' => $contact->phone,
                'isPrimary' => (bool) $contact->is_primary,
                'isActive' => (bool) $contact->is_active,
                'version' => $contact->row_version,
            ])->values() : [],
            'version' => $supplier->row_version,
        ];
    }
}
