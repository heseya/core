<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Support\Str;

abstract class OrderEvent extends WebHookEvent
{
    protected Order $order;

    public function __construct(Order $order)
    {
        parent::__construct();
        $this->order = $order;
    }

    public function getData(): array
    {
        return [
            'data' => $this->order->toArray(),
            'data_type' => Str::remove('App\\Models\\', $this->order::class),
            'event' => Str::remove('App\\Events\\', $this::class),
            'triggered_at' => $this->triggered_at,
        ];
    }
}
