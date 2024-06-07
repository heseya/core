<?php

declare(strict_types=1);

namespace Domain\Order\Resources;

use App\Http\Resources\Resource;
use App\Traits\MetadataResource;
use Domain\ShippingMethod\Models\ShippingMethod;
use Illuminate\Http\Request;

/**
 * @property ShippingMethod $resource
 */
final class OrderShippingMethodResource extends Resource
{
    use MetadataResource;

    /**
     * @return array<string, mixed>
     */
    public function base(Request $request): array
    {
        return array_merge([
            'id' => $this->resource->getKey(),
            'name' => $this->resource->name,
            'shipping_type' => $this->resource->shipping_type,
            'payment_on_delivery' => $this->resource->payment_on_delivery,
            'integration_key' => $this->resource->integration_key,
        ], $this->metadataResource('shipping_methods.show_metadata_private'));
    }
}
