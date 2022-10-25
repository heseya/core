<?php

namespace App\Services;

use App\Dtos\FavouriteProductSetDto;
use App\Enums\ExceptionsEnums\Exceptions;
use App\Exceptions\ClientException;
use App\Http\Resources\FavouriteProductSetResource;
use App\Models\FavouriteProductSet;
use App\Services\Contracts\FavouriteServiceContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\Response as ResponseStatus;

class FavouriteService implements FavouriteServiceContract
{
    public function storeFavouriteProductSet(FavouriteProductSetDto $dto): FavouriteProductSet
    {
        return Auth::user()->favouriteProductSets()->create($dto->toArray());
    }

    public function showProductSet(string $id): JsonResource|JsonResponse
    {
        $favouriteProductSet = Auth::user()->favouriteProductSets()->where('product_set_id', $id)->first();
        return $favouriteProductSet ? FavouriteProductSetResource::make($favouriteProductSet)
            : Response::json(null, ResponseStatus::HTTP_NOT_FOUND);
    }

    public function index(): LengthAwarePaginator
    {
        return Auth::user()->favouriteProductSets()->paginate(Config::get('pagination.per_page'));
    }

    /**
     * @throws ClientException
     */
    public function destroy(string $id): JsonResponse
    {
        $favouriteProductSet = Auth::user()->favouriteProductSets()->where('product_set_id', $id)->first();

        if (!$favouriteProductSet) {
            throw new ClientException(Exceptions::PRODUCT_SET_IS_NOT_ON_FAVOURITES_LIST);
        }

        $favouriteProductSet->delete();

        return Response::json(null, ResponseStatus::HTTP_NO_CONTENT);
    }

    public function destroyAll(): JsonResponse
    {
        Auth::user()->favouriteProductSets()->delete();

        return Response::json(null, ResponseStatus::HTTP_NO_CONTENT);
    }
}
