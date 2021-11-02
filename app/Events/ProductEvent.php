<?php

namespace App\Events;

use App\Http\Resources\ProductResource;
use App\Models\Product;

abstract class ProductEvent extends WebHookEvent
{
    protected Product $product;

    public function __construct(Product $product)
    {
        parent::__construct();
        $this->product = $product;
    }

    public function isHidden(): bool
    {
        return !$this->product->isPublic();
    }

    public function getDataContent(): array
    {
        return ProductResource::make($this->product)->resolve();
    }

    public function getDataType(): string
    {
        return $this->getModelClass($this->product);
    }
}
