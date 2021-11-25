<?php

namespace App\Events;

use App\Models\Order;

class OrderCreated extends OrderEvent
{
    public function getOrder(): Order
    {
        return $this->order;
    }
}
