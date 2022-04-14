<?php

namespace App\Dtos;

class WeekDayInConditionDto extends ConditionDto
{
    private array $weekday;

    public static function fromArray(array $array): self
    {
        return new self(
            type: $array['type'],
            weekday: $array['weekday'],
        );
    }

    public function getWeekday(): array
    {
        return $this->weekday;
    }
}
