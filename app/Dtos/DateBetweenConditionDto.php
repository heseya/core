<?php

namespace App\Dtos;

use Heseya\Dto\Missing;
use Illuminate\Support\Str;

class DateBetweenConditionDto extends ConditionDto
{
    private string|Missing $start_at;
    private string|Missing $end_at;
    private bool $is_in_range;

    public static function fromArray(array $array): self
    {
        $start_at = array_key_exists('start_at', $array) ? $array['start_at'] : new Missing();
        $start_at = !$start_at instanceof Missing && !Str::contains($start_at, ':')
            ? Str::before($start_at, 'T'). 'T00:00:00' : $start_at;

        $end_at = array_key_exists('end_at', $array) ? $array['end_at'] : new Missing();
        $end_at = !$end_at instanceof Missing && !Str::contains($end_at, ':')
            ? Str::before($end_at, 'T'). 'T23:59:59' : $end_at;

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
