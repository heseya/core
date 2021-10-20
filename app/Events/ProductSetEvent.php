<?php

namespace App\Events;

use App\Models\ProductSet;
use Illuminate\Support\Str;

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
        return $this->productSet->toArray();
    }

    public function getDataType(): string
    {
        return $this->getModelClass($this->productSet);
    }
}
