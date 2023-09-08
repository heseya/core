<?php

namespace App\Listeners;

use App\Events\ProductSearchValueEvent;
use App\Services\Contracts\ProductServiceContract;

class ProductSearchValueListener
{
    public function __construct(
        private ProductServiceContract $productService,
    ) {}

    public function handle(ProductSearchValueEvent $event): void
    {
        $this->productService->updateProductsSearchValues($event->getProductIds());
    }
}
