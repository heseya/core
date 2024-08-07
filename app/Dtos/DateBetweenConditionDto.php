<?php

namespace App\Dtos;

use Heseya\Dto\Missing;

class DateBetweenConditionDto extends ConditionDto
{
    private Missing|string $start_at;
    private Missing|string $end_at;
    private bool $is_in_range;

    public static function fromArray(array $array): self
    {
        $start_at = array_key_exists('start_at', $array) ? $array['start_at'] : new Missing();
        $end_at = array_key_exists('end_at', $array) ? $array['end_at'] : new Missing();

        return new self(
            type: $array['type'],
            start_at: $start_at,
            end_at: $end_at,
            is_in_range: $array['is_in_range'],
        );
    }

    public function getStartAt(): Missing|string
    {
        return $this->start_at;
    }

    public function getEndAt(): Missing|string
    {
        return $this->end_at;
    }

    public function isIsInRange(): bool
    {
        return $this->is_in_range;
    }
}
