<?php

namespace App\Events;

use App\Models\ProductSet;

abstract class ProductSetEvent extends WebHookEvent
{
    private ProductSet $productSet;

    public function __construct(ProductSet $productSet)
    {
        $this->productSet = $productSet;
    }

    public function getData(): array
    {
        return $this->productSet->toArray();
    }

    public function isHidden(): bool
    {
        return $this->productSet->public;
    }
}
