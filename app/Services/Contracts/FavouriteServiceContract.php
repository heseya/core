<?php

namespace App\Services\Contracts;

use App\Dtos\FavouriteProductSetDto;
use App\Models\FavouriteProductSet;
use Illuminate\Pagination\LengthAwarePaginator;

interface FavouriteServiceContract
{
    public function storeFavouriteProductSet(FavouriteProductSetDto $dto): ?FavouriteProductSet;
    public function showProductSet(string $id): ?FavouriteProductSet;
    public function index(): ?LengthAwarePaginator;
    public function destroy(string $id): void;
    public function destroyAll(): void;
}
