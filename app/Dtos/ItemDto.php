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

    public string|Missing $name;
    public string|Missing $sku;
    public int|null|Missing $unlimited_stock_shipping_time;
    public string|null|Missing $unlimited_stock_shipping_date;
    public array|Missing $metadata;

    public static function instantiateFromRequest(FormRequest $request): self
    {
        return new self(
            name: $request->input('name', new Missing()),
            sku: $request->input('sku', new Missing()),
            unlimited_stock_shipping_time: $request->input('unlimited_stock_shipping_time', new Missing()),
            unlimited_stock_shipping_date: $request->input('unlimited_stock_shipping_date', new Missing()),
            metadata: self::mapMetadata($request),
        );
    }

    public function getName(): Missing|string
    {
        return $this->name;
    }

    public function getSku(): Missing|string
    {
        return $this->sku;
    }

    public function getUnlimitedStockShippingTime(): Missing|null|int
    {
        return $this->unlimited_stock_shipping_time;
    }

    public function getUnlimitedStockShippingDate(): Missing|null|string
    {
        return $this->unlimited_stock_shipping_date;
    }

    public function getMetadata(): Missing|array
    {
        return $this->metadata;
    }
}
