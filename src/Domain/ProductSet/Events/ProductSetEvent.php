<?php

declare(strict_types=1);

namespace Domain\ProductSet\Events;

use App\Events\WebHookEvent;
use Domain\ProductSet\ProductSet;
use Domain\ProductSet\Resources\ProductSetResource;

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

    /**
     * @return array<string, mixed>
     */
    public function getDataContent(): array
    {
        return ProductSetResource::make($this->productSet)->resolve();
    }

    public function getDataType(): string
    {
        return $this->getModelClass($this->productSet);
    }

    public function getProductSet(): ProductSet
    {
        return $this->productSet;
    }
}
