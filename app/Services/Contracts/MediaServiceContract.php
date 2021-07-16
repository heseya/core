<?php

namespace App\Services\Contracts;

use App\Models\Media;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

interface MediaServiceContract
{
    public function sync(Product $product, array $media): void;

    public function store(Request $request): JsonResource;

    public function destroyByImage(Media $media): JsonResponse;
}
