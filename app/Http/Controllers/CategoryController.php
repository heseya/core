<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Swagger\CategoryControllerSwagger;
use App\Http\Requests\ProductSetIndexRequest;
use App\Http\Resources\ProductSetResource;
use App\Services\Contracts\ProductSetServiceContract;
use App\Services\ProductSetService;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryController extends Controller implements CategoryControllerSwagger
{
    private ProductSetService $productSetService;

    public function __construct(ProductSetServiceContract $productSetService)
    {
        $this->productSetService = $productSetService;
    }

    /**
     * @deprecated
     */
    public function index(ProductSetIndexRequest $request): JsonResource
    {
        $sets = $this->productSetService->categories($request->validated());

        return ProductSetResource::collection($sets);
    }
}
