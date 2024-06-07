<?php

declare(strict_types=1);

namespace Domain\Order\Resources;

use App\Http\Resources\Resource;
use Domain\SalesChannel\Models\SalesChannel;
use Illuminate\Http\Request;

/**
 * @property SalesChannel $resource
 */
final class OrderSalesChannelResource extends Resource
{
    /**
     * @return array<string, mixed>
     */
    public function base(Request $request): array
    {
        return [
            'id' => $this->resource->getKey(),
            'name' => $this->resource->name,
            'slug' => $this->resource->slug,
        ];
    }
}
