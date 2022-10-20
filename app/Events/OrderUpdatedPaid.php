<?php

namespace App\Events;

use App\Models\Order;

class OrderUpdatedPaid extends OrderEvent
{
    public function __construct(Order $order)
    {
        parent::__construct($order);
        $this->order->refresh();
    }

    public function getOrder(): Order
    {
        return $this->order;
    }
}
