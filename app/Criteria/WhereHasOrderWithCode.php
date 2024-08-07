<?php

namespace App\Criteria;

use App\Models\Order;
use Domain\PaymentMethods\Enums\PaymentMethodType;
use Heseya\Searchable\Criteria\Criterion;
use Illuminate\Database\Eloquent\Builder;

class WhereHasOrderWithCode extends Criterion
{
    public function query(Builder $query): Builder
    {
        /** @var Order $order */
        $order = Order::query()->where('code', '=', $this->value)->first();

        if ($order->payment_method_type === PaymentMethodType::POSTPAID) {
            return $query->where('id', '=', null);
        }

        return $query
            ->where('type', '=', $order->payment_method_type)
            ->whereHas('shippingMethods', function (Builder $query): void {
                $query
                    ->whereHas('orders', function (Builder $query): void {
                        $query->where('code', $this->value);
                    })
                    ->orWhereHas('digitalOrders', function (Builder $query): void {
                        $query->whereNull('shipping_method_id')->where('code', $this->value);
                    });
            });
    }
}
