<?php

namespace App\Events;

use App\Models\Product;

abstract class ProductEvent extends WebHookEvent
{
    private Product $product;

    public function __construct(Product $product)
    {
        $this->product = $product;
    }

    public function getData(): array
    {
        return $this->product->toArray();
    }

    public function isHidden(): bool
    {
        return $this->product->public;
    }
}
