<?php

namespace App\Dtos;

use Heseya\Dto\Dto;
use Heseya\Dto\Missing;
use Illuminate\Support\Collection;

abstract class CartOrderDto extends Dto
{
    abstract public function getProductIds(): array;

    abstract public function getProductIdsWithDiscounts(): Collection;

    abstract public function getCartLength(): float|int;

    abstract public function getCoupons(): array|Missing;
}
