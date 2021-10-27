<?php

namespace App\Events;

use App\Http\Resources\ProductSetResource;
use App\Models\ProductSet;

abstract class ProductSetEvent extends WebHookEvent
{
    protected ProductSet $productSet;

    public function __construct(ProductSet $productSet)
    {
        parent::__construct();
        $this->productSet = $productSet;
    }

    public function isHidden(): bool
    {
        return !$this->productSet->public;
    }

    public function getDataContent(): array
    {
        return ProductSetResource::make($this->productSet)->resolve();
    }

    public function getDataType(): string
    {
        return $this->getModelClass($this->productSet);
    }
}
