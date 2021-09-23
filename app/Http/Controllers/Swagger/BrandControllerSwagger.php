<?php

namespace App\Http\Controllers\Swagger;

use App\Http\Requests\ProductSetIndexRequest;
use Illuminate\Http\Resources\Json\JsonResource;

interface BrandControllerSwagger
{
    public function index(ProductSetIndexRequest $request): JsonResource;
}
