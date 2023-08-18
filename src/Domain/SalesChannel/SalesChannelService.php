<?php

declare(strict_types=1);

namespace Domain\SalesChannel;

use App\Enums\ExceptionsEnums\Exceptions;
use App\Exceptions\ClientException;
use App\Models\CartItemResponse;
use App\Models\CartResource;
use Illuminate\Support\Facades\Cache;

final readonly class SalesChannelService
{
    public function applyVatOnCartItems(CartResource $cart): CartResource
    {
        $cart_total = 0;

        /** @var CartItemResponse $item */
        foreach ($cart->items as $item) {
            $item->price = $this->calcVat($item->price);
            $item->price_discounted = $this->calcVat($item->price_discounted);
            $cart_total += $item->price_discounted * $item->quantity;
        }

        $cart->cart_total = $cart_total;

        return $cart;
    }

    private function calcVat(float $price): float
    {
        $vat_rate = Cache::get('vat_rate');

        if (!is_float($vat_rate)) {
            throw new ClientException(Exceptions::CLIENT_SALES_CHANNEL_NOT_FOUND);
        }

        return round($price + ($price * $vat_rate), 2, PHP_ROUND_HALF_UP);
    }
}
