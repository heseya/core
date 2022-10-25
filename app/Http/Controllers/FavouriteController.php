<?php

namespace App\Http\Controllers;

use App\Dtos\FavouriteProductSetDto;
use App\Http\Requests\FavouriteProductSetStoreRequest;
use App\Http\Resources\FavouriteProductSetResource;
use App\Models\ProductSet;
use App\Services\Contracts\FavouriteServiceContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

class FavouriteController extends Controller
{
    public function __construct(
        private FavouriteServiceContract $favouriteService,
    ) {
    }

    public function store(FavouriteProductSetStoreRequest $request): JsonResource
    {
        return FavouriteProductSetResource::make(
            $this->favouriteService->storeFavouriteProductSet(
                FavouriteProductSetDto::instantiateFromRequest($request)
            )
        );
    }

    public function show(ProductSet $productSet): JsonResource|JsonResponse
    {
        return $this->favouriteService->showProductSet($productSet->getKey());
    }

    public function index(): JsonResource
    {
        return FavouriteProductSetResource::collection(
            $this->favouriteService->index(),
        );
    }

    public function destroy(ProductSet $productSet): JsonResponse
    {
        return $this->favouriteService->destroy($productSet->getKey());
    }

    public function destroyAll(): JsonResponse
    {
        return $this->favouriteService->destroyAll();
    }
}
