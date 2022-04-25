<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class AppWithSavedAddressesResource extends AppResource
{
    public function base(Request $request): array
    {
        return array_merge(
            parent::base($request),
            [
                'delivery_addresses' => SavedAddressResource::collection($this->resource->deliveryAddresses),
                'invoice_addresses' => SavedAddressResource::collection($this->resource->invoiceAddresses),
            ],
        );
    }

    public function view(Request $request): array
    {
        return [
            'permissions' => $this->resource->getAllPermissions()
                ->map(fn ($perm) => $perm->name)
                ->sort()
                ->values(),
        ];
    }
}
