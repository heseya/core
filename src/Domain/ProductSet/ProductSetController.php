<?php

declare(strict_types=1);

namespace Domain\ProductSet;

use App\Dtos\ProductsReorderDto;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use Domain\ProductSet\Dtos\ProductSetCreateDto;
use Domain\ProductSet\Dtos\ProductSetIndexDto;
use Domain\ProductSet\Dtos\ProductSetUpdateDto;
use Domain\ProductSet\Requests\ProductSetAttachRequest;
use Domain\ProductSet\Requests\ProductSetProductReorderRequest;
use Domain\ProductSet\Requests\ProductSetReorderRequest;
use Domain\ProductSet\Resources\ProductSetParentResource;
use Domain\ProductSet\Resources\ProductSetResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

final class ProductSetController extends Controller
{
    public function __construct(
        private readonly ProductSetService $productSetService,
    ) {}

    public function index(ProductSetIndexDto $dto): JsonResource
    {
        $sets = $this->productSetService->searchAll($dto);

        return ProductSetResource::collection($sets);
    }

    public function show(ProductSet $productSet): JsonResource
    {
        $this->productSetService->authorize($productSet);

        return ProductSetParentResource::make($productSet);
    }

    public function store(ProductSetCreateDto $dto): JsonResource
    {
        $productSet = $this->productSetService->create($dto);

        return ProductSetParentResource::make($productSet);
    }

    public function update(ProductSet $productSet, ProductSetUpdateDto $dto): JsonResource
    {
        $productSet = $this->productSetService->update($productSet, $dto);

        return ProductSetParentResource::make($productSet);
    }

    public function reorder(ProductSet $productSet, ProductSetReorderRequest $request): JsonResponse
    {
        $this->productSetService->reorder($request->input('product_sets'), $productSet);

        return Response::json(null, ResponseAlias::HTTP_NO_CONTENT);
    }

    public function attach(ProductSet $productSet, ProductSetAttachRequest $request): JsonResource
    {
        $products = $this->productSetService->attach(
            $productSet,
            $request->input('products', []),
        );

        return ProductResource::collection($products);
    }

    public function destroy(ProductSet $productSet): JsonResponse
    {
        $this->productSetService->delete($productSet);

        return Response::json(null, ResponseAlias::HTTP_NO_CONTENT);
    }

    public function products(ProductSet $productSet): JsonResource
    {
        $products = $this->productSetService->products($productSet);

        return ProductResource::collection($products);
    }

    public function reorderProducts(ProductSet $productSet, ProductSetProductReorderRequest $request): JsonResponse
    {
        $dto = ProductsReorderDto::instantiateFromRequest($request);
        $this->productSetService->reorderProducts($productSet, $dto);

        return Response::json(null, ResponseAlias::HTTP_NO_CONTENT);
    }
}
