<?php

namespace App\Http\Controllers;

use App\Dtos\ProductSearchDto;
use App\Dtos\SeoMetadataDto;
use App\Events\ProductCreated;
use App\Events\ProductDeleted;
use App\Events\ProductUpdated;
use App\Http\Requests\ProductCreateRequest;
use App\Http\Requests\ProductIndexRequest;
use App\Http\Requests\ProductShowRequest;
use App\Http\Requests\ProductUpdateRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryContract;
use App\Services\Contracts\AttributeServiceContract;
use App\Services\Contracts\MediaServiceContract;
use App\Services\Contracts\ProductServiceContract;
use App\Services\Contracts\SchemaServiceContract;
use App\Services\Contracts\SeoMetadataServiceContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ProductController extends Controller
{
    public function __construct(
        private MediaServiceContract $mediaService,
        private SchemaServiceContract $schemaService,
        private ProductServiceContract $productService,
        private SeoMetadataServiceContract $seoMetadataService,
        private AttributeServiceContract $attributeService,
        private ProductRepositoryContract $productRepository,
    ) {
    }

    public function index(ProductIndexRequest $request): JsonResource
    {
        $products = $this->productRepository->search(
            ProductSearchDto::instantiateFromRequest($request),
        );

        return ProductResource::collection($products)->full($request->has('full'));
    }

    public function show(ProductShowRequest $request, Product $product): JsonResource
    {
        if (Gate::denies('products.show_hidden') && !$product->public) {
            throw new NotFoundHttpException();
        }

        return ProductResource::make($product);
    }

    public function store(ProductCreateRequest $request): JsonResource
    {
        $product = Product::create($request->validated());

        $this->productService->assignItems($product, $request->items);

        $this->productSetup($product, $request);

        $seo_dto = SeoMetadataDto::fromFormRequest($request);
        $product->seo()->save($this->seoMetadataService->create($seo_dto));

        ProductCreated::dispatch($product);

        return ProductResource::make($product);
    }

    public function update(ProductUpdateRequest $request, Product $product): JsonResource
    {
        $product->update($request->validated());

        $this->productService->assignItems($product, $request->items);

        $this->productSetup($product, $request);

        if ($request->has('seo')) {
            $seo_dto = SeoMetadataDto::fromFormRequest($request);
            $this->seoMetadataService->update($seo_dto, $product->seo);
        }

        ProductUpdated::dispatch($product);

        return ProductResource::make($product);
    }

    public function destroy(Product $product): JsonResponse
    {
        $this->mediaService->sync($product);

        if ($product->delete()) {
            ProductDeleted::dispatch($product);
            if ($product->seo !== null) {
                $this->seoMetadataService->delete($product->seo);
            }
        }

        return Response::json(null, 204);
    }

    /**
     * TODO: move this to service
     *
     * @param Product $product
     * @param ProductCreateRequest|ProductUpdateRequest $request
     */
    private function productSetup(
        Product $product,
        ProductCreateRequest|ProductUpdateRequest $request,
    ): void {
        $this->mediaService->sync($product, $request->input('media', []));
        $product->tags()->sync($request->input('tags', []));

        if ($request->has('schemas')) {
            $this->schemaService->sync($product, $request->input('schemas'));
        }

        $this->productService->updateMinMaxPrices($product);

        if ($request->has('sets')) {
            $product->sets()->sync($request->input('sets'));
        }

        if ($request->has('attributes')) {
            $this->attributeService->sync($product, $request->input('attributes'));
        }
    }
}
