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
use App\Services\Contracts\SchemaServiceContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ProductController extends Controller implements ProductControllerSwagger
{
    private MediaServiceContract $mediaService;
    private SchemaServiceContract $schemaService;

    public function __construct(MediaServiceContract $mediaService, SchemaServiceContract $schemaService)
    {
        $this->mediaService = $mediaService;
        $this->schemaService = $schemaService;
    }

    public function index(ProductIndexRequest $request): JsonResource
    {
        $query = Product::search($request->validated())
            ->sort($request->input('sort', 'order'));

        if (!Auth::user()->can('products.show_hidden')) {
            if (!Auth::user()->can('product_sets.show_hidden')) {
                $query->public();
            } else {
                $query->where('public', true);
            }
        }

        if ($request->has('sets')) {
            if (!Auth::user()->can('product_sets.show_hidden')) {
                $setsFound = ProductSet::public()->whereIn(
                    'slug',
                    $request->input('sets'),
                )->count();

                if ($setsFound < count($request->input('sets'))) {
                    throw new ModelNotFoundException('Can\'t find the given product set');
                }
            }

            $query->whereHas('sets', function (Builder $query) use ($request) {
                return $query->whereIn(
                    'slug',
                    $request->input('sets'),
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
        if (!Auth::user()->can('products.show_hidden') && !$product->isPublic()) {
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
