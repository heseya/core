<?php

namespace App\Events;

use App\Models\Order;
use Domain\Order\Resources\OrderResource;

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
        return OrderResource::make($this->order)->resolve();
    }

    public function getDataType(): string
    {
        return $this->getModelClass($this->order);
    }
}
