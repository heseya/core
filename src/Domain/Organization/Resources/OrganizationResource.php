<?php

declare(strict_types=1);

namespace Domain\Organization\Resources;

use App\Http\Resources\AddressResource;
use App\Http\Resources\Resource;
use Domain\SalesChannel\Resources\SalesChannelResource;
use Illuminate\Http\Request;

final class OrganizationResource extends Resource
{
    /**
     * @return array<string, mixed>
     */
    public function base(Request $request): array
    {
        return [
            'id' => $this->resource->getKey(),
            'client_id' => $this->resource->client_id,
            'billing_email' => $this->resource->billing_email,
            'billing_address' => AddressResource::make($this->resource->address),
            'sales_channel' => SalesChannelResource::make($this->resource->salesChannel),
        ];
    }
}
