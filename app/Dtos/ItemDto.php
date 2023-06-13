<?php

namespace App\Dtos;

use App\Dtos\Contracts\InstantiateFromRequest;
use App\Traits\MapMetadata;
use Heseya\Dto\Dto;
use Heseya\Dto\Missing;
use Illuminate\Foundation\Http\FormRequest;

class ItemDto extends Dto implements InstantiateFromRequest
{
    use MapMetadata;

    public function __construct(
        public readonly string|Missing $id,
        public readonly string|Missing $name,
        public readonly string|Missing $sku,
        public readonly int|null|Missing $unlimited_stock_shipping_time,
        public readonly string|null|Missing $unlimited_stock_shipping_date,
        public readonly array|Missing $metadata,
    ) {
    }

    public static function instantiateFromRequest(FormRequest $request): self
    {
        return new self(
            id: $request->input('id') ?? new Missing(),
            name: $request->input('name', new Missing()),
            sku: $request->input('sku', new Missing()),
            unlimited_stock_shipping_time: $request->input('unlimited_stock_shipping_time', new Missing()),
            unlimited_stock_shipping_date: $request->input('unlimited_stock_shipping_date', new Missing()),
            metadata: self::mapMetadata($request),
        );
    }
}
