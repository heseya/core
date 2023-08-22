<?php

namespace App\Dtos;

use App\Dtos\Contracts\InstantiateFromRequest;
use App\Enums\ExceptionsEnums\Exceptions;
use App\Exceptions\ServerException;
use App\Http\Requests\CartRequest;
use Domain\Currency\Currency;
use Heseya\Dto\DtoException;
use Heseya\Dto\Missing;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;

class CartDto extends CartOrderDto implements InstantiateFromRequest
{
    public function __construct(
        public readonly Currency $currency,
        public array $items,
        public readonly array|Missing $coupons,
        public readonly Missing|string $shipping_method_id,
        public readonly Missing|string $digital_shipping_method_id,
        public readonly string $sales_channel_id,
    ) {}

    /**
     * @throws DtoException
     * @throws ServerException
     */
    public static function instantiateFromRequest(CartRequest|FormRequest $request): self
    {
        $currency = $request->enum('currency', Currency::class);

        if ($currency === null) {
            throw new ServerException(Exceptions::SERVER_PRICE_UNKNOWN_CURRENCY);
        }

        return new self(
            currency: $currency,
            items: self::prepareItems($request->input('items', [])),
            coupons: $request->input('coupons', new Missing()),
            shipping_method_id: $request->input('shipping_method_id', new Missing()),
            digital_shipping_method_id: $request->input('digital_shipping_method_id', new Missing()),
            sales_channel_id: $request->input('sales_channel_id'),
        );
    }

    /**
     * @throws DtoException
     */
    public static function fromArray(array $array): self
    {
        return new self(
            currency: $array['currency'],
            items: self::prepareItems($array['items']),
            coupons: $array['coupons'],
            shipping_method_id: $array['shipping_method_id'],
            digital_shipping_method_id: $array['digital_shipping_method_id'],
            sales_channel_id: $array['sales_channel_id'],
        );
    }

    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * @param array<CartItemDto> $items
     */
    public function setItems(array $items): void
    {
        $this->items = $items;
    }

    public function getCoupons(): array|Missing
    {
        return $this->coupons;
    }

    public function getShippingMethodId(): Missing|string
    {
        return $this->shipping_method_id;
    }

    public function getDigitalShippingMethodId(): Missing|string
    {
        return $this->digital_shipping_method_id;
    }

    public function getProductIds(): array
    {
        $result = [];
        /** @var CartItemDto $item */
        foreach ($this->items as $item) {
            $result[] = $item->getProductId();
        }

        return $result;
    }

    public function getCartLength(): float|int
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

                return $cartItem->getCartItemId() === $item['cartitem_id']
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
