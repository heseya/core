<?php

namespace App\Dtos;

class MaxUsesConditionDto extends ConditionDto
{
    private int $max_uses;

    public static function fromArray(array $array): self
    {
        return new self(
            type: $array['type'],
            max_uses: $array['max_uses'],
        );
    }

    public function getMaxUses(): int
    {
        return $this->max_uses;
    }
}
