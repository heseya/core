<?php

namespace App\Dtos;

use Heseya\Dto\Missing;

class CartLengthConditionDto extends ConditionDto
{
    private int|float|Missing $min_value;
    private int|float|Missing $max_value;

    public static function fromArray(array $array): self
    {
        return new self(
            type: $array['type'],
            min_value: array_key_exists('min_value', $array) ? $array['min_value'] : new Missing(),
            max_value: array_key_exists('max_value', $array) ? $array['max_value'] : new Missing(),
        );
    }

    public function getMinValue(): int|float|Missing
    {
        return $this->min_value;
    }

    public function getMaxValue(): int|float|Missing
    {
        return $this->max_value;
    }
}
