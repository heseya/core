<?php

namespace App\Events;

use App\Models\ProductSet;

abstract class ProductSetEvent extends WebHookEvent
{
    protected ProductSet $productSet;

    public function __construct(ProductSet $productSet)
    {
        parent::__construct();
        $this->productSet = $productSet;
    }

    public function getData(): array
    {
        return [
            'data' => $this->productSet->toArray(),
            'data_type' => Str::remove('App\\Models\\', $this->productSet::class),
            'event' => Str::remove('App\\Events\\', $this::class),
            'triggered_at' => $this->triggered_at,
        ];
    }

    public function isHidden(): bool
    {
        return !$this->productSet->public;
    }
}
