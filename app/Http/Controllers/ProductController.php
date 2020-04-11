<?php

namespace App\Http\Controllers;

use App\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Resources\BrandResource;
use App\Http\Resources\MediaResource;
use App\Http\Resources\ProductResource;
use App\Http\Resources\CategoryResource;
use Illuminate\Database\Eloquent\Builder;
use App\Http\Resources\ProductShortResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ProductController extends Controller
{
    /**
     * @OA\Get(
     *   path="/products",
     *   summary="list products",
     *   @OA\Response(
     *     response=200,
     *     description="success",
     *     @OA\MediaType(
     *       mediaType="application/json",
     *       @OA\Schema(
     *         @OA\Property(
     *           property="data",
     *           type="array",
     *             @OA\Items(ref="#/components/schemas/Product"),
     *         )
     *       )
     *     )
     *   )
     * )
     */
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

        return ProductShortResource::collection(
            $query->paginate(12)
        );
    }

    /**
     * @OA\Get(
     *   path="/products/{slug}",
     *   summary="prodct info",
     *   @OA\Response(
     *     response=200,
     *     description="success",
     *     @OA\MediaType(
     *       mediaType="application/json",
     *       @OA\Schema(
     *         @OA\Property(
     *           property="data",
     *           ref="#/components/schemas/Product"
     *         )
     *       )
     *     )
     *   )
     * )
     */
    public function view(Product $product): ProductResource
    {
        return new ProductResource($product);
    }
}
