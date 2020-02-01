<?php

namespace App\Http\Controllers\Store;

use App\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Http\Resources\BrandResource;
use App\Http\Resources\MediaResource;
use App\Http\Resources\ProductResource;
use App\Http\Resources\CategoryResource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ProductsController extends Controller
{
    public function index(Request $request): ResourceCollection
    {
        $request->validate([
            'brand' => ['string', 'max:80'],
            'category' => ['string', 'max:80'],
            'q' => ['string', 'max:80'],
        ]);

        $query = Product::with([
            'brand',
            'category',
            'gallery',
        ]);

        if ($request->brand) {
            $query->whereHas('brand', function (Builder $query) use ($request) {
                return $query->where('slug', $request->brand);
            });
        }

        if ($request->category) {
            $query->whereHas('category', function (Builder $query) use ($request) {
                return $query->where('slug', $request->category);
            });
        }

        if ($request->q) {
            $query->where('slug', 'LIKE', '%' . $request->q . '%')
                ->orWhere('name', 'LIKE', '%' . $request->q . '%');
        }

        return ProductResource::collection(
            $query->simplePaginate(12)
        );
    }

    public function view(Product $product): JsonResponse
    {
        return response()->json([
            'slug' => $product->slug,
            'name' => $product->name,
            'price' => $product->price,
            'description' => $product->parsed_description,
            'brand' => new BrandResource($product->brand),
            'category' => new CategoryResource($product->category),
            'gallery' => MediaResource::collection($product->gallery),
            'schema' => $product->schema,
        ]);
    }
}
