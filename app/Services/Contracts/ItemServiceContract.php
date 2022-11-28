<?php

namespace App\Services\Contracts;

use App\Dtos\ItemDto;
use App\Models\Item;
use Illuminate\Support\Collection;

interface ItemServiceContract
{
    public function addItemArrays(array $items1, array $items2): array;

    public function validateItems(array $items): void;

    public function checkOrderItems(array $items): Collection;

    public function checkCartItems(array $items): Collection;

    public function checkHasItemType(Collection $items, ?bool $physical, ?bool $digital): bool;

    public function store(ItemDto $dto): Item;

    public function update(Item $item, ItemDto $dto): Item;

    public function destroy(Item $item): void;

    public function refreshSerchable(Item $item): void;
}
