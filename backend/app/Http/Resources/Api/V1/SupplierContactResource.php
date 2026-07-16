<?php

namespace App\Http\Resources\Api\V1;

use App\Domains\Procurement\Models\SupplierContact;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property SupplierContact $resource
 */
class SupplierContactResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $contact = $this->resource;

        return [
            'id' => (string) $contact->id,
            'supplierId' => (string) $contact->supplier_id,
            'fullName' => $contact->full_name,
            'jobTitle' => $contact->job_title,
            'email' => $contact->email,
            'phone' => $contact->phone,
            'isPrimary' => (bool) $contact->is_primary,
            'isActive' => (bool) $contact->is_active,
            'version' => $contact->row_version,
        ];
    }
}
