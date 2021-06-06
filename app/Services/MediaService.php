<?php

namespace App\Services;

use App\Models\Product;
use App\Services\Contracts\MediaServiceContract;
use App\Services\Contracts\ReorderServiceContract;

class MediaService implements MediaServiceContract
{
    protected ReorderServiceContract $reorderService;

    public function __construct(ReorderServiceContract $reorderService)
    {
        $this->reorderService = $reorderService;
    }

    public function sync(Product $product, array $media = []): void
    {
        $product->media()->sync($this->reorderService->reorder($media));
    }
}
