<?php

namespace App\Http\Controllers;

use App\Dtos\ProductSetDto;
use App\Http\Controllers\Swagger\ProductSetControllerSwagger;
use App\Http\Requests\ProductSetIndexRequest;
use App\Http\Requests\ProductSetReorderRequest;
use App\Http\Requests\ProductSetShowRequest;
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

    public function index(ProductSetIndexRequest $request): JsonResource
    {
        $sets = $this->productSetService->searchAll($request->validated());

        if ($request->has('tree') && $request->input('tree', true) !== false) {
            return ProductSetTreeResource::collection($sets);
        }
        return ProductSetResource::collection($sets);
    }

    public function show(ProductSet $productSet, ProductSetShowRequest $request): JsonResource
    {
        $this->productSetService->authorize($productSet);

        if ($request->has('tree') && $request->input('tree', true) !== false) {
            return ProductSetTreeResource::make($productSet);
        }

        return ProductSetResource::make($productSet);
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
}
