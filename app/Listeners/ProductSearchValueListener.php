<?php

namespace App\Listeners;

use App\Events\ProductSearchValueEvent;
use App\Services\Contracts\ProductServiceContract;
use Illuminate\Contracts\Queue\ShouldQueue;

readonly class ProductSearchValueListener implements ShouldQueue
{
    public function __construct(
        private ProductServiceContract $productService,
    ) {}

    public function handle(ProductSearchValueEvent $event): void
    {
        $this->productService->updateProductsSearchValues($event->product_ids);
    }
}
