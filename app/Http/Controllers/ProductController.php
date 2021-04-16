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

        $this->mediaService->sync($product, $request->input('media', []));

        if ($request->has('schemas') && is_array($request->input('schemas'))) {
            $this->schemaService->sync($product, $request->input('schemas'));
        }

        return ProductResource::make($product);
    }

    public function update(ProductUpdateRequest $request, Product $product): JsonResource
    {
        $product->update($request->validated());

        $this->mediaService->sync($product, $request->input('media', []));

        if ($request->has('schemas') && is_array($request->input('schemas'))) {
            $this->schemaService->sync($product, $request->input('schemas'));
        }

        return ProductResource::make($product);
    }

    public function destroy(Product $product): JsonResponse
    {
        $product->delete();

        return response()->json(null, 204);
    }
}
