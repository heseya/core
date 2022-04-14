<?php

namespace App\Http\Controllers;

use App\Dtos\ProductCreateDto;
use App\Dtos\ProductSearchDto;
use App\Dtos\ProductUpdateDto;
use App\Http\Requests\ProductCreateRequest;
use App\Http\Requests\ProductIndexRequest;
use App\Http\Requests\ProductUpdateRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryContract;
use App\Services\Contracts\DiscountServiceContract;
use App\Services\Contracts\ProductServiceContract;
use Heseya\Resource\ResourceCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ProductController extends Controller
{
    public function __construct(
        private ProductServiceContract $productService,
        private ProductRepositoryContract $productRepository,
        private DiscountServiceContract $discountService,
    ) {
    }

    public function index(ProductIndexRequest $request): JsonResource
    {
        $products = $this->productRepository->search(
            ProductSearchDto::instantiateFromRequest($request),
        );

        /** @var ResourceCollection $productsCollection */
        $productsCollection = ProductResource::collection($this->discountService->applyDiscountsOnProducts($products->items()));

        return ProductResource::collection($this->discountService->applyDiscountsOnProducts($products->items()))
            ->full($request->has('full'));
    }

    public function show(Product $product): JsonResource
    {
        if (Gate::denies('products.show_hidden') && !$product->public) {
            throw new NotFoundHttpException();
        }

        return ProductResource::make($this->discountService->applyDiscountsOnProduct($product));
    }

    public function store(ProductCreateRequest $request): JsonResource
    {
        $product = $this->productService->create(
            ProductCreateDto::instantiateFromRequest($request),
        );

        return ProductResource::make($this->discountService->applyDiscountsOnProduct($product));
    }

    public function update(ProductUpdateRequest $request, Product $product): JsonResource
    {
        $product = $this->productService->update(
            $product,
            ProductUpdateDto::instantiateFromRequest($request),
        );

        return ProductResource::make($this->discountService->applyDiscountsOnProduct($product));
    }

    public function destroy(Product $product): JsonResponse
    {
        $this->productService->delete($product);

        return Response::json(null, 204);
    }
}
