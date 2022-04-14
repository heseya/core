<?php

namespace App\Dtos;

use Heseya\Dto\Dto;

abstract class CartOrderDto extends Dto
{
    abstract public function getProductIds(): array;

    abstract public function getCartLength(): int;
}
