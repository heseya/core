<?php

namespace App\Http\Controllers;

use App\Dtos\ProductSetDto;
use App\Http\Controllers\Swagger\ProductSetControllerSwagger;
use App\Http\Requests\ProductSetIndexRequest;
use App\Http\Requests\ProductSetProductsRequest;
use App\Http\Requests\ProductSetReorderRequest;
use App\Http\Requests\ProductSetShowRequest;
use App\Http\Requests\ProductSetStoreRequest;
use App\Http\Requests\ProductSetUpdateRequest;
use App\Http\Resources\ProductResource;
use App\Http\Resources\ProductSetChildrenResource;
use App\Http\Resources\ProductSetParentChildrenResource;
use App\Http\Resources\ProductSetParentResource;
use App\Http\Resources\ProductSetResource;
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

    public function index(ProductSetIndexRequest $request): JsonResource
    {
        $sets = $this->productSetService->searchAll(
            $request->validated(),
            $request->has('tree') && $request->input('tree', true) !== false ||
            $request->has('root') && $request->input('root', true) !== false
        );

        if ($request->has('tree') && $request->input('tree', true) !== false) {
            return ProductSetChildrenResource::collection($sets);
        }

        return ProductSetResource::collection($sets);
    }

    public function show(ProductSet $productSet, ProductSetShowRequest $request): JsonResource
    {
        $this->productSetService->authorize($productSet);

        if ($request->has('tree') && $request->input('tree', true) !== false) {
            return ProductSetParentChildrenResource::make($productSet);
        }

        return ProductSetParentResource::make($productSet);
    }

    public function store(ProductSetStoreRequest $request): JsonResource
    {
        $dto = ProductSetDto::instantiateFromRequest($request);
        $productSet = $this->productSetService->create($dto);

        if ($request->has('tree') && $request->input('tree', true) !== false) {
            return ProductSetParentChildrenResource::make($productSet);
        }

        return ProductSetParentResource::make($productSet);
    }

    public function update(ProductSet $productSet, ProductSetUpdateRequest $request): JsonResource
    {
        $dto = ProductSetDto::instantiateFromRequest($request);
        $productSet = $this->productSetService->update($productSet, $dto);

        if ($request->has('tree') && $request->input('tree', true) !== false) {
            return ProductSetParentChildrenResource::make($productSet);
        }

        return ProductSetParentResource::make($productSet);
    }

    public function reorder(ProductSet $productSet, ProductSetReorderRequest $request): JsonResponse
    {
        $this->productSetService->reorder($productSet, $request->input('product_sets'));

        return Response::json(null, JsonResponse::HTTP_NO_CONTENT);
    }

    public function destroy(ProductSet $productSet): JsonResponse
    {
        $this->productSetService->delete($productSet);

        return Response::json(null, JsonResponse::HTTP_NO_CONTENT);
    }

    public function products(ProductSet $productSet, ProductSetProductsRequest $request): JsonResource
    {
        $products = $this->productSetService->products($productSet, (int) $request->input('limit', 12));

        return ProductResource::collection($products);
    }
}
