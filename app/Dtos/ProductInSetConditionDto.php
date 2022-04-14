<?php

namespace App\Dtos;

class ProductInSetConditionDto extends ConditionDto
{
    private array $product_sets;
    private bool $is_allow_list;

    public static function fromArray(array $array): self
    {
        return new self(
            type: $array['type'],
            product_sets: $array['product_sets'],
            is_allow_list: $array['is_allow_list'],
        );
    }

    public function getProductSets(): array
    {
        return $this->product_sets;
    }

    public function isIsAllowList(): bool
    {
        return $this->is_allow_list;
    }
}
