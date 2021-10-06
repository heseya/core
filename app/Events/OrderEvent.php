<?php

namespace App\Events;

use App\Models\Order;

abstract class OrderEvent extends WebHookEvent
{
    protected Order $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function getData(): array
    {
        return $this->order->toArray();
    }
}
