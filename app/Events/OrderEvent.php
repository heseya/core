<?php

namespace App\Events;

use App\Models\Order;

abstract class OrderEvent extends WebHookEvent
{
    protected Order $order;

    public function __construct(Order $order)
    {
        parent::__construct();
        $this->order = $order;
    }

    public function getDataContent(): array
    {
        return $this->order->toArray();
    }

    public function getDataType(): string
    {
        return $this->getModelClass($this->order);
    }
}
