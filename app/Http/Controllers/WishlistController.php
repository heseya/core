<?php

namespace App\Http\Controllers;

use App\Http\Requests\WishlistProductStoreRequest;
use App\Http\Resources\WishlistProductResource;
use App\Models\App;
use App\Models\Product;
use App\Models\User;
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
    ) {}

    public function index(Request $request): JsonResource
    {
        /** @var User|App $user */
        $user = $request->user();

        return WishlistProductResource::collection(
            $this->wishlistService->index($user),
        );
    }

    public function show(Request $request, Product $product): JsonResource|JsonResponse
    {
        /** @var User|App $user */
        $user = $request->user();
        $wishlistProduct = $this->wishlistService->show($user, $product);

        return $wishlistProduct === null ?
            Response::json(null, ResponseAlias::HTTP_NOT_FOUND) :
            WishlistProductResource::make($wishlistProduct);
    }

    public function store(WishlistProductStoreRequest $request): JsonResource
    {
        /** @var User|App $user */
        $user = $request->user();

        return WishlistProductResource::make(
            $this->wishlistService->storeWishlistProduct(
                $user,
                $request->input('product_id'),
            ),
        );
    }

    public function destroy(Request $request, Product $product): JsonResponse
    {
        /** @var User|App $user */
        $user = $request->user();

        $this->wishlistService->destroy($user, $product);

        return Response::json(null, ResponseAlias::HTTP_NO_CONTENT);
    }

    public function destroyAll(Request $request): JsonResponse
    {
        /** @var User|App $user */
        $user = $request->user();

        $this->wishlistService->destroyAll($user);

        return Response::json(null, ResponseAlias::HTTP_NO_CONTENT);
    }
}
