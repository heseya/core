<?php

namespace App\Events;

use App\Models\Product;
use Illuminate\Support\Str;

abstract class ProductEvent extends WebHookEvent
{
    protected Product $product;

    public function __construct(Product $product)
    {
        parent::__construct();
        $this->product = $product;
    }

    public function getData(): array
    {
        return [
            'data' => $this->product->toArray(),
            'data_type' => Str::remove('App\\Models\\', $this->product::class),
            'event' => Str::remove('App\\Events\\', $this::class),
            'triggered_at' => $this->triggered_at,
        ];
    }

    public function isHidden(): bool
    {
        return !$this->product->isPublic();
    }
}
