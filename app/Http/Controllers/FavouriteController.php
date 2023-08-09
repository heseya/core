<?php

namespace App\Http\Controllers;

use App\Dtos\FavouriteProductSetDto;
use App\Http\Requests\FavouriteProductSetStoreRequest;
use App\Http\Resources\FavouriteProductSetResource;
use App\Services\Contracts\FavouriteServiceContract;
use Domain\ProductSet\ProductSet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\Response as ResponseStatus;

class FavouriteController extends Controller
{
    public function __construct(
        private FavouriteServiceContract $favouriteService,
    ) {}

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
        $favouriteProductSet = $this->favouriteService->showProductSet($productSet->getKey());

        return $favouriteProductSet ? FavouriteProductSetResource::make($favouriteProductSet)
            : Response::json(null, ResponseStatus::HTTP_NO_CONTENT);
    }

    public function index(): JsonResource
    {
        return FavouriteProductSetResource::collection(
            $this->favouriteService->index(),
        );
    }

    public function destroy(ProductSet $productSet): JsonResponse
    {
        $this->favouriteService->destroy($productSet->getKey());

        return Response::json(null, ResponseStatus::HTTP_NO_CONTENT);
    }

    public function destroyAll(): JsonResponse
    {
        $this->favouriteService->destroyAll();

        return Response::json(null, ResponseStatus::HTTP_NO_CONTENT);
    }
}
