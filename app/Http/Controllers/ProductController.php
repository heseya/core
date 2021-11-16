<?php

namespace App\Http\Controllers;

use App\Dtos\SeoMetadataDto;
use App\Http\Controllers\Swagger\ProductControllerSwagger;
use App\Http\Requests\ProductCreateRequest;
use App\Http\Requests\ProductIndexRequest;
use App\Http\Requests\ProductShowRequest;
use App\Http\Requests\ProductUpdateRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\ProductSet;
use App\Services\Contracts\MediaServiceContract;
use App\Services\Contracts\ProductServiceContract;
use App\Services\Contracts\ProductSetServiceContract;
use App\Services\Contracts\SchemaServiceContract;
use App\Services\Contracts\SeoMetadataServiceContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ProductController extends Controller implements ProductControllerSwagger
{
    public function __construct(
        private MediaServiceContract $mediaService,
        private SchemaServiceContract $schemaService,
        private ProductServiceContract $productService,
        private ProductSetServiceContract $productSetService,
        private SeoMetadataServiceContract $seoMetadataService
    ) {
    }

    public function index(ProductIndexRequest $request): JsonResource
    {
        $query = Product::search($request->validated())
            ->sort($request->input('sort', 'order'))
            ->with(['media', 'tags', 'schemas', 'sets', 'seo']);

        $canShowHiddenSets = Gate::allows('product_sets.show_hidden');

        if (Gate::denies('products.show_hidden')) {
            if (!$canShowHiddenSets) {
                $query->public();
            } else {
                $query->where('public', true);
            }
        }

        if ($request->has('sets')) {
            $setsFound = ProductSet::whereIn(
                'slug',
                $request->input('sets') ?? [],
            )->with('allChildren')->get();

            $relationScope = $canShowHiddenSets ? 'allChildren' : 'allChildrenPublic';

            $setsFlat = $this->productSetService
                ->flattenSetsTree($setsFound, $relationScope)
                ->map(fn (ProductSet $set) => $set->slug);

            $query->whereHas('sets', function (Builder $query) use ($setsFlat) {
                return $query->whereIn(
                    'slug',
                    $setsFlat,
                );
            });
        }

        if (!$request->hasAny(['sets', 'search', 'name', 'slug', 'public'])) {
            $query->whereDoesntHave('sets', function (Builder $query) {
                return $query->where('hide_on_index', true);
            });
        }

        $products = $query->paginate(Config::get('pagination.per_page'));

        if ($request->has('available')) {
            $products = $products->filter(function ($product) use ($request) {
                return $product->available === $request->boolean('available');
            });
        }

        return ProductResource::collection($products)->full($request->has('full'));
    }

    public function show(ProductShowRequest $request, Product $product): JsonResource
    {
        if (Gate::denies('products.show_hidden') && !$product->isPublic()) {
            throw new NotFoundHttpException();
        }

        return ProductResource::make($product);
    }

    public function store(ProductCreateRequest $request): JsonResource
    {
        $product = Product::create($request->validated());

        $this->productSetup($product, $request);

        $seo_dto = SeoMetadataDto::fromFormRequest($request);
        $product->seo()->save($this->seoMetadataService->create($seo_dto));

        return ProductResource::make($product);
    }

    public function update(ProductUpdateRequest $request, Product $product): JsonResource
    {
        $product->update($request->validated());

        $this->productSetup($product, $request);

        if ($request->has('seo')) {
            $seo_dto = SeoMetadataDto::fromFormRequest($request);
            $this->seoMetadataService->update($seo_dto, $product->seo);
        }

        return ProductResource::make($product);
    }

    public function destroy(Product $product): JsonResponse
    {
        $product->delete();

        if ($product->seo !== null) {
            $this->seoMetadataService->delete($product->seo);
        }

        return Response::json(null, 204);
    }

    /**
     * @param Product $product
     * @param ProductCreateRequest|ProductUpdateRequest $request
     */
    public function productSetup(
        Product $product,
        ProductCreateRequest|ProductUpdateRequest $request,
    ) {
        $this->mediaService->sync($product, $request->input('media', []));
        $product->tags()->sync($request->input('tags', []));

        if ($request->has('schemas')) {
            $this->schemaService->sync($product, $request->input('schemas'));
        }

        $this->productService->updateMinMaxPrices($product);

        if ($request->has('sets')) {
            $product->sets()->sync($request->input('sets'));
        }
    }
}
