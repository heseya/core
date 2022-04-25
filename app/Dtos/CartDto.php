<?php

namespace App\Dtos;

use App\Dtos\Contracts\InstantiateFromRequest;
use App\Http\Requests\CartRequest;
use Heseya\Dto\Missing;
use Illuminate\Foundation\Http\FormRequest;

class CartDto extends CartOrderDto implements InstantiateFromRequest
{
    private array $items;
    private array|Missing $coupons;
    private string|Missing $shipping_method_id;

    public static function instantiateFromRequest(FormRequest|CartRequest $request): self
    {
        $items = [];
        foreach ($request->input('items', []) as $item) {
            array_push($items, CartItemDto::fromArray($item));
        }
        return new self(
            items: $items,
            coupons: $request->input('coupons', new Missing()),
            shipping_method_id: $request->input('shipping_method_id', new Missing()),
        );
    }

    public static function fromArray(array $array): self
    {
        $items = [];
        foreach ($array['items'] as $item) {
            array_push($items, CartItemDto::fromArray($item));
        }
        return new self(
            items: $items,
            coupons: $array['coupons'],
            shipping_method_id: $array['shipping_method_id'],
        );
    }

    public function getItems(): array
    {
        return $this->items;
    }

    public function getCoupons(): Missing|array
    {
        return $this->coupons;
    }

    public function getShippingMethodId(): Missing|string
    {
        return $this->shipping_method_id;
    }

    public function getProductIds(): array
    {
        $result = [];
        /** @var CartItemDto $item */
        foreach ($this->items as $item) {
            array_push($result, $item->getProductId());
        }
        return $result;
    }

    public function getCartLength(): int
    {
        $length = 0;
        /** @var CartItemDto $item */
        foreach ($this->items as $item) {
            $length += $item->getQuantity();
        }
        return $length;
    }
}
