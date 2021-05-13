<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderStatusUpdated
{
    use Dispatchable, SerializesModels;

    private Order $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
        $this->order->refresh();
    }

    public function getOrder(): Order
    {
        return $this->order;
    }
}
