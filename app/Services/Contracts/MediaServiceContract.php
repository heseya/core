<?php

namespace App\Services\Contracts;

use App\Http\Requests\MediaStoreRequest;
use App\Models\Product;
use Illuminate\Http\Resources\Json\JsonResource;

interface MediaServiceContract
{
    public function sync(Product $product, array $media): void;

    public function store(MediaStoreRequest $request): JsonResource;
}
