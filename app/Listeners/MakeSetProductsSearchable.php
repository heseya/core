<?php

namespace App\Listeners;

use App\Events\ProductSetCreated;
use App\Events\ProductSetUpdated;
use App\Services\Contracts\ProductSetServiceContract;

class MakeSetProductsSearchable
{
    public function __construct(
        private ProductSetServiceContract $productSetService,
    ) {}

    /**
     * Handle the event.
     */
    public function handle(ProductSetCreated|ProductSetUpdated $event): void
    {
        $this->productSetService->indexAllProducts($event->getProductSet());
    }
}
