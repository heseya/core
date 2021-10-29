<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Swagger\ProductControllerSwagger;
use App\Http\Requests\ProductCreateRequest;
use App\Http\Requests\ProductIndexRequest;
use App\Http\Requests\ProductShowRequest;
use App\Http\Requests\ProductUpdateRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\ProductSet;
use App\Services\Contracts\MediaServiceContract;
use App\Services\Contracts\ProductSetServiceContract;
use App\Services\Contracts\SchemaServiceContract;
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
        private ProductSetServiceContract $productSetService,
    ) {
    }

    public function index(ProductIndexRequest $request): JsonResource
    {
        $query = Product::search($request->validated())
            ->sort($request->input('sort', 'order'))
            ->with(['media', 'tags', 'schemas', 'sets']);

        if (Gate::denies('products.show_hidden')) {
            if (Gate::denies('product_sets.show_hidden')) {
                $query->public();
            } else {
                $query->where('public', true);
            }
        }

        if ($request->has('sets')) {
            $setsFound = ProductSet::whereIn(
                'slug',
                $request->input('sets'),
            )->with('allChildren')->get();

            $setsFlat = $this->productSetService
                ->flattenSetsTree($setsFound, 'allChildren')
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

        $this->mediaService->sync($product, $request->input('media', []));
        $product->tags()->sync($request->input('tags', []));

        if ($request->has('schemas')) {
            $this->schemaService->sync($product, $request->input('schemas'));
        }

        if ($request->has('sets')) {
            $product->sets()->sync($request->input('sets'));
        }

        return ProductResource::make($product);
    }

    public function update(ProductUpdateRequest $request, Product $product): JsonResource
    {
        $product->update($request->validated());

        $this->mediaService->sync($product, $request->input('media', []));
        $product->tags()->sync($request->input('tags', []));

        if ($request->has('schemas')) {
            $this->schemaService->sync($product, $request->input('schemas'));
        }

        if ($request->has('sets')) {
            $product->sets()->sync($request->input('sets'));
        }

        return ProductResource::make($product);
    }

    public function destroy(Product $product): JsonResponse
    {
        $product->delete();

        return Response::json(null, 204);
    }
}
