<?php

namespace App\Events;

use App\Models\Discount;
use Illuminate\Support\Str;

abstract class DiscountEvent extends WebHookEvent
{
    protected Discount $discount;

    public function __construct(Discount $discount)
    {
        parent::__construct();
        $this->discount = $discount;
    }

    public function getData(): array
    {
        return [
            'data' => $this->discount->toArray(),
            'data_type' => Str::remove('App\\Models\\', $this->discount::class),
            'event' => Str::remove('App\\Events\\', $this::class),
            'triggered_at' => $this->triggered_at,
        ];
    }
}
