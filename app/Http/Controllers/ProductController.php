<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Swagger\ProductControllerSwagger;
use App\Http\Requests\ProductCreateRequest;
use App\Http\Requests\ProductIndexRequest;
use App\Http\Requests\ProductShowRequest;
use App\Http\Requests\ProductUpdateRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\Contracts\MediaServiceContract;
use App\Services\Contracts\SchemaServiceContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
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
            ->sort($request->input('sort', 'order'))
            ->with([
                'brand',
                'category',
                'tags',
                'media',
            ]);

        if (!Auth::user()->can('products.show_hidden')) {
            $query->public();

            if ($request->has('brand')) {
                $query->whereHas('brand', function (Builder $query) use ($request): Builder {
                    $query->where('public', true)->where('public_parent', true);

                    if (!$request->has('search')) {
                        $query->where(function (Builder $query) use ($request): Builder {
                            return $query
                                ->where('hide_on_index', false)
                                ->orWhere('slug', $request->input('brand'));
                        });
                    }

                    return $query;
                });
            }

            if ($request->has('category')) {
                $query->whereHas('category', function (Builder $query) use ($request): Builder {
                    $query->where('public', true)->where('public_parent', true);

                    if (!$request->has('search')) {
                        $query->where(function (Builder $query) use ($request): Builder {
                            return $query
                                ->where('hide_on_index', false)
                                ->orWhere('slug', $request->input('category'));
                        });
                    }

                    return $query;
                });
            }

            if ($request->has('set')) {
                $query->whereHas('sets', function (Builder $query) use ($request): Builder {
                    $query->where('public', true)->where('public_parent', true);

                    if (!$request->has('search')) {
                        $query->where(function (Builder $query) use ($request): Builder {
                            return $query
                                ->where('hide_on_index', false)
                                ->orWhere('slug', $request->input('sets'));
                        });
                    }

                    return $query;
                });
            }
        }

        $products = $query->paginate((int) $request->input('limit', 12));

        if ($request->has('available')) {
            $products = $products->filter(function ($product) use ($request) {
                return $product->available === $request->boolean('available');
            });
        }

        return ProductResource::collection(
            $products,
            $request->has('full'),
        );
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
