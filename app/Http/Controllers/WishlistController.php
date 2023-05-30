<?php

namespace App\Http\Controllers;

use App\Http\Requests\WishlistProductStoreRequest;
use App\Http\Resources\WishlistProductResource;
use App\Models\Product;
use App\Services\Contracts\WishlistServiceContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class WishlistController extends Controller
{
    public function __construct(
        private readonly WishlistServiceContract $wishlistService,
    ) {
    }

    public function index(Request $request): JsonResource
    {
        return WishlistProductResource::collection(
            $this->wishlistService->index($request->user()),
        );
    }

    public function show(Request $request, Product $product): JsonResource|JsonResponse
    {
        if (!$this->wishlistService->canView($request->user(), $product)) {
            return Response::json(null, ResponseAlias::HTTP_NOT_FOUND);
        }

        return WishlistProductResource::make($product);
    }

    public function store(WishlistProductStoreRequest $request): JsonResource
    {
        return WishlistProductResource::make(
            $this->wishlistService->storeWishlistProduct(
                $request->user(),
                $request->input('product_id'),
            ),
        );
    }

    public function destroy(Request $request, Product $product): JsonResponse
    {
        $this->wishlistService->destroy($request->user(), $product);

        return Response::json(null, ResponseAlias::HTTP_NO_CONTENT);
    }

    public function destroyAll(Request $request): JsonResponse
    {
        $this->wishlistService->destroyAll($request->user());

        return Response::json(null, ResponseAlias::HTTP_NO_CONTENT);
    }
}
