<?php

namespace App\Http\Controllers;

use App\Http\Requests\WishlistProductStoreRequest;
use App\Http\Resources\WishlistProductResource;
use App\Models\Product;
use App\Services\Contracts\WishlistServiceContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class WishlistController extends Controller
{
    public function __construct(private WishlistServiceContract $wishlistService)
    {
    }

    public function index(): JsonResource
    {
        return WishlistProductResource::collection(
            Auth::user()?->wishlistProducts()->paginate(Config::get('pagination.per_page'))
        );
    }

    public function show(Product $product): JsonResource|JsonResponse
    {
        $wishlistProduct = Auth::user()?->wishlistProducts()->where('product_id', $product->getKey())->first();

        return $wishlistProduct === null ?
            Response::json(null, ResponseAlias::HTTP_NOT_FOUND) : WishlistProductResource::make($wishlistProduct);
    }

    public function store(WishlistProductStoreRequest $request): JsonResource
    {
        return WishlistProductResource::make(
            $this->wishlistService->storeWishlistProduct(
                $request->input('product_id'),
            ),
        );
    }

    public function destroy(Product $product): JsonResponse
    {
        $this->wishlistService->destroy($product);

        return Response::json(null, ResponseAlias::HTTP_NO_CONTENT);
    }

    public function destroyAll(): JsonResponse
    {
        $this->wishlistService->destroyAll();

        return Response::json(null, ResponseAlias::HTTP_NO_CONTENT);
    }
}
