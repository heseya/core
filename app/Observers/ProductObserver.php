<?php

namespace App\Observers;

use App\Events\ProductUpdated;
use App\Models\Product;
use App\Services\Contracts\AvailabilityServiceContract;

class ProductObserver
{
    private AvailabilityServiceContract $availabilityService;

    public function __construct(AvailabilityServiceContract $availabilityService)
    {
        $this->availabilityService = $availabilityService;
    }

    public function created(Product $product)
    {
        $this->availabilityService->calculateProductAvailability($product);
    }

    public function updating(Product $product)
    {
        if ($product->isDirty('available')) {
            ProductUpdated::dispatch($product);
        }
    }
}
