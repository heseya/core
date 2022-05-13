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
    public int|Missing $unlimited_stock_shipping_time;
    public string|Missing $unlimited_stock_shipping_date;
    public int|Missing $shipping_time;
    public string|Missing $shipping_date;
    public array|Missing $metadata;

    public static function instantiateFromRequest(FormRequest $request): self
    {
        return new self(
            name: $request->input('name', new Missing()),
            sku: $request->input('sku', new Missing()),
            unlimited_stock_shipping_time: $request->input('unlimited_stock_shipping_time', new Missing()),
            unlimited_stock_shipping_date: $request->input('unlimited_stock_shipping_date', new Missing()),
            shipping_time: $request->input('shipping_time', new Missing()),
            shipping_date: $request->input('shipping_date', new Missing()),
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

    public function getUnlimitedStockShippingTime(): Missing|int
    {
        return $this->unlimited_stock_shipping_time;
    }

    public function getUnlimitedStockShippingDate(): Missing|string
    {
        return $this->unlimited_stock_shipping_date;
    }

    public function getShippingTime(): Missing|int
    {
        return $this->shipping_time;
    }

    public function getShippingDate(): Missing|string
    {
        return $this->shipping_date;
    }

    public function getMetadata(): Missing|array
    {
        return $this->metadata;
    }
}
