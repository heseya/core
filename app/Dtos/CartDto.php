<?php

namespace App\Dtos;

use App\Dtos\Contracts\InstantiateFromRequest;
use App\Http\Requests\CartRequest;
use Heseya\Dto\Missing;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;

class CartDto extends CartOrderDto implements InstantiateFromRequest
{
    private array $items;
    private array|Missing $coupons;
    private string|Missing $shipping_method_id;

    public static function instantiateFromRequest(FormRequest|CartRequest $request): self
    {
        return new self(
            items: self::prepareItems($request->input('items', [])),
            coupons: $request->input('coupons', new Missing()),
            shipping_method_id: $request->input('shipping_method_id', new Missing()),
        );
    }

    public static function fromArray(array $array): self
    {
        return new self(
            items: self::prepareItems($array['items']),
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

    private static function prepareItems(array $items): array
    {
        $result = Collection::make();
        foreach ($items as $item) {
            $existingItem = $result->first(function ($cartItem) use ($item) {
                $schemas = array_key_exists('schemas', $item) ? $item['schemas'] : [];
                return $cartItem->getCartitemId() === $item['cartitem_id']
                    && $cartItem->getProductId() === $item['product_id']
                    && count(array_diff($cartItem->getSchemas(), $schemas)) === 0;
            });
            // @phpstan-ignore-next-line
            if ($existingItem) {
                $existingItem->setQuantity($existingItem->getQuantity() + $item['quantity']);
            } else {
                $result->push(CartItemDto::fromArray($item));
            }
        }
        return $result->toArray();
    }
}
