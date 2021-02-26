<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Swagger\ProductControllerSwagger;
use App\Http\Requests\ProductCreateRequest;
use App\Http\Requests\ProductIndexRequest;
use App\Http\Requests\ProductShowRequest;
use App\Http\Requests\ProductUpdateRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller implements ProductControllerSwagger
{
    public function index(ProductIndexRequest $request): JsonResource
    {
        $query = Product::search($request->validated())
            ->sort($request->input('sort'))
            ->with([
                'brand',
                'media',
                'category',
            ]);

        if (!Auth::check()) {
            $query
                ->where('public', true)
                ->whereHas('brand', fn (Builder $q) => $q->where('public', true))
                ->whereHas('category', fn (Builder $q) => $q->where('public', true));
        }

        return ProductResource::collection(
            $query->paginate((int) $request->input('limit', 12)),
            $request->has('full'),
        );
    }

    public function show(ProductShowRequest $request, Product $product): JsonResource
    {
        return ProductResource::make($product);
    }

    public function store(ProductCreateRequest $request): JsonResource
    {
        $product = Product::create($request->validated());

        $product->media()->sync($request->input('media', []));

        if ($request->has('schemas') && is_array($request->input('schemas'))) {
            $product->schemas()->sync($request->input('schemas'));
        }

        return ProductResource::make($product);
    }

    public function update(ProductUpdateRequest $request, Product $product): JsonResource
    {
        $product->update($request->validated());

        $product->media()->sync($request->input('media', []));

        if ($request->has('schemas') && is_array($request->input('schemas'))) {
            $product->schemas()->sync($request->input('schemas'));
        }

        return ProductResource::make($product);
    }

    public function destroy(Product $product): JsonResponse
    {
        $product->delete();

        return response()->json(null, 204);
    }
}
