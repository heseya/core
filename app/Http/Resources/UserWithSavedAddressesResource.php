<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class UserWithSavedAddressesResource extends UserResource
{
    public function base(Request $request): array
    {
        return array_merge(
            parent::base($request),
            [
                'shipping_addresses' => SavedAddressResource::collection($this->resource->shippingAddresses),
                'billing_addresses' => SavedAddressResource::collection($this->resource->billingAddresses),
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
            'preferences' => UserPreferencesResource::make($this->resource->preferences),
        ];
    }
}
