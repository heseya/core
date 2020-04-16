<?php

namespace App\Http\Controllers;

use App\Error;
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
     *   tags={"Products"},
     *   @OA\Parameter(
     *     name="search",
     *     in="query",
     *     description="Full text search.",
     *     @OA\Schema(
     *       type="string",
     *     ),
     *   ),
     *   @OA\Parameter(
     *     name="brand",
     *     in="query",
     *     description="Brand slug.",
     *     @OA\Schema(
     *       type="string",
     *     ),
     *   ),
     *   @OA\Parameter(
     *     name="category",
     *     in="query",
     *     description="Category slug.",
     *     @OA\Schema(
     *       type="string",
     *     ),
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         type="array",
     *         @OA\Items(ref="#/components/schemas/Product"),
     *       )
     *     )
     *   )
     * )
     */
    public function index(Request $request): ResourceCollection
    {
        $request->validate([
            'brand' => ['string', 'max:255'],
            'category' => ['string', 'max:255'],
            'search' => ['string', 'max:255'],
        ]);

        $query = Product::with([
            'brand',
            'category',
            'gallery',
        ]);

        $query
            ->where('public', true)
            ->whereHas('brand', fn (Builder $query) => $query->where('public', true))
            ->whereHas('category', fn (Builder $query) => $query->where('public', true));

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

        if ($request->search) {
            $query
                ->where('slug', 'LIKE', '%' . $request->search . '%')
                ->orWhere('name', 'LIKE', '%' . $request->search . '%')
                ->orWhereHas('brand', function (Builder $query) use ($request) {
                    return $query->where('name', 'LIKE', '%' . $request->search . '%')
                        ->orWhere('slug', 'LIKE', '%' . $request->search . '%');
                })
                ->orWhereHas('category', function (Builder $query) use ($request) {
                    return $query->where('name', 'LIKE', '%' . $request->search . '%')
                        ->orWhere('slug', 'LIKE', '%' . $request->search . '%');
                });
        }

        return ProductShortResource::collection(
            $query->paginate(12)
        );
    }

    /**
     * @OA\Get(
     *   path="/products/{slug}",
     *   summary="prodct info",
     *   tags={"Products"},
     *   @OA\Parameter(
     *     name="slug",
     *     in="query",
     *     required=true,
     *     @OA\Schema(
     *       type="string",
     *     )
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\JsonContent(
     *       @OA\Property(
     *         property="data",
     *         ref="#/components/schemas/Product"
     *       )
     *     )
     *   )
     * )
     */
    public function view(Product $product)
    {
        if ($product->isPublic() !== true) {
            return Error::abort('Unauthorized.', 401);
        }

        return new ProductResource($product);
    }
}
