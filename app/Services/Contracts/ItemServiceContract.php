<?php

namespace App\Services\Contracts;

interface ItemServiceContract
{
    public function addItemArrays(array $items1, array $items2): array;

    public function validateItems(array $items): void;
}
