<?php

declare(strict_types=1);

namespace Domain\Organization\Resources;

use App\Http\Resources\AddressResource;
use App\Http\Resources\Resource;
use Illuminate\Http\Request;

final class OrganizationSavedAddressResource extends Resource
{
    /**
     * @return array<string, mixed>
     */
    public function base(Request $request): array
    {
        return [
            'id' => $this->resource->getKey(),
            'name' => $this->resource->name,
            'default' => $this->resource->default,
            'address' => AddressResource::make($this->resource->address),
            'change_version' => $this->resource->change_version,
        ];
    }
}
