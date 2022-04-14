<?php

namespace App\Dtos;

class ProductInConditionDto extends ConditionDto
{
    private array $products;
    private bool $is_allow_list;

    public static function fromArray(array $array): self
    {
        return new self(
            type: $array['type'],
            products: $array['products'],
            is_allow_list: $array['is_allow_list'],
        );
    }

    public function getProducts(): array
    {
        return $this->products;
    }

    public function isIsAllowList(): bool
    {
        return $this->is_allow_list;
    }
}
