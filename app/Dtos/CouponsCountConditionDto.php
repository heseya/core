<?php

namespace App\Dtos;

use Heseya\Dto\Missing;

class CouponsCountConditionDto extends ConditionDto
{
    private int|Missing $min_value;
    private int|Missing $max_value;

    public static function fromArray(array $array): self
    {
        return new self(
            type: $array['type'],
            min_value: array_key_exists('min_value', $array) ? $array['min_value'] : new Missing(),
            max_value: array_key_exists('max_value', $array) ? $array['max_value'] : new Missing(),
        );
    }

    public function getMinValue(): int|Missing
    {
        return $this->min_value;
    }

    public function getMaxValue(): int|Missing
    {
        return $this->max_value;
    }
}
