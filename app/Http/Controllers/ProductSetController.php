<?php

namespace App\Http\Controllers;

use App\Dtos\ProductSetDto;
use App\Http\Controllers\Swagger\ProductSetControllerSwagger;
use App\Http\Requests\CategoryIndexRequest;
use App\Http\Requests\CategoryReorderRequest;
use App\Http\Requests\ProductSetStoreRequest;
use App\Http\Requests\ProductSetUpdateRequest;
use App\Http\Resources\ProductSetResource;
use App\Http\Resources\ProductSetTreeResource;
use App\Models\ProductSet;
use App\Services\Contracts\ProductSetServiceContract;
use App\Services\ProductSetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Response;

class ProductSetController extends Controller implements ProductSetControllerSwagger
{
    private ProductSetService $productSetService;

    public function __construct(ProductSetServiceContract $productSetService)
    {
        $this->productSetService = $productSetService;
    }

    public function index(CategoryIndexRequest $request): JsonResource
    {
        $sets = $this->productSetService->searchAll($request->validated());

        if ($request->has('tree') && $request->input('tree', true) !== false) {
            return ProductSetTreeResource::collection($sets);
        }
        return ProductSetResource::collection($sets);
    }

    public function store(ProductSetStoreRequest $request): JsonResource
    {
        $dto = ProductSetDto::instantiateFromRequest($request);

        return ProductSetResource::make(
            $this->productSetService->create($dto),
        );
    }

    public function update(ProductSet $productSet, ProductSetUpdateRequest $request): JsonResource
    {
        $dto = ProductSetDto::instantiateFromRequest($request);

        return ProductSetResource::make(
            $this->productSetService->update($productSet, $dto),
        );
    }

    public function reorder(CategoryReorderRequest $request): JsonResponse
    {
        $this->productSetService->reorder($request->input('product_sets'));

        return Response::json(null, JsonResponse::HTTP_NO_CONTENT);
    }

    public function destroy(ProductSet $productSet): JsonResponse
    {
        $this->productSetService->delete($productSet);

        return Response::json(null, JsonResponse::HTTP_NO_CONTENT);
    }
}
