<?php

namespace App\Dtos;

class OnSaleConditionDto extends ConditionDto
{
    private bool $on_sale;

    public static function fromArray(array $array): self
    {
        return new self(
            type: $array['type'],
            on_sale: $array['on_sale'],
        );
    }

    public function getOnSale(): bool
    {
        return $this->on_sale;
    }
}
