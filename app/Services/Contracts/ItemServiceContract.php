<?php

namespace App\Services\Contracts;

use Illuminate\Support\Collection;

interface ItemServiceContract
{
    public function addItemArrays(array $items1, array $items2): array;

    public function validateItems(array $items): void;

    public function checkOrderItems(array $items): Collection;

    public function checkCartItems(array $items): Collection;
}
