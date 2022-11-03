<?php

namespace App\Services;

use App\Dtos\FavouriteProductSetDto;
use App\Enums\ExceptionsEnums\Exceptions;
use App\Exceptions\ClientException;
use App\Models\FavouriteProductSet;
use App\Services\Contracts\FavouriteServiceContract;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;

class FavouriteService implements FavouriteServiceContract
{
    public function storeFavouriteProductSet(FavouriteProductSetDto $dto): FavouriteProductSet
    {
        return Auth::user()->favouriteProductSets()->create($dto->toArray());
    }

    public function showProductSet(string $id): ?FavouriteProductSet
    {
        return Auth::user()->favouriteProductSets()->where('product_set_id', $id)->first();
    }

    public function index(): LengthAwarePaginator
    {
        return Auth::user()->favouriteProductSets()->paginate(Config::get('pagination.per_page'));
    }

    /**
     * @throws ClientException
     */
    public function destroy(string $id): void
    {
        $favouriteProductSet = Auth::user()->favouriteProductSets()->where('product_set_id', $id)->first();

        if (!$favouriteProductSet) {
            throw new ClientException(Exceptions::PRODUCT_SET_IS_NOT_ON_FAVOURITES_LIST);
        }

        $favouriteProductSet->delete();
    }

    public function destroyAll(): void
    {
        Auth::user()->favouriteProductSets()->delete();
    }
}
