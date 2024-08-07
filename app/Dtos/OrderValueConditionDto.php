<?php

namespace App\Dtos;

use Heseya\Dto\Missing;

class OrderValueConditionDto extends ConditionDto
{
    private float|Missing $min_value;
    private float|Missing $max_value;
    private bool $include_taxes;
    private bool $is_in_range;

    public static function fromArray(array $array): self
    {
        return new self(
            type: $array['type'],
            min_value: array_key_exists('min_value', $array) ? $array['min_value'] : new Missing(),
            max_value: array_key_exists('max_value', $array) ? $array['max_value'] : new Missing(),
            include_taxes: $array['include_taxes'],
            is_in_range: $array['is_in_range'],
        );
    }

    public function getMinValue(): float|Missing
    {
        return $this->min_value;
    }

    public function getMaxValue(): float|Missing
    {
        return $this->max_value;
    }

    public function isIncludeTaxes(): bool
    {
        return $this->include_taxes;
    }

    public function isIsInRange(): bool
    {
        return $this->is_in_range;
    }
}
