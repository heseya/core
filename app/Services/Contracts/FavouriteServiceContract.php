<?php

namespace App\Services\Contracts;

use App\Dtos\FavouriteProductSetDto;
use App\Models\FavouriteProductSet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Pagination\LengthAwarePaginator;

interface FavouriteServiceContract
{
    public function storeFavouriteProductSet(FavouriteProductSetDto $dto): FavouriteProductSet;
    public function showProductSet(string $id): JsonResource|JsonResponse;
    public function index(): LengthAwarePaginator;
    public function destroy(string $id): JsonResponse;
    public function destroyAll(): JsonResponse;
}
