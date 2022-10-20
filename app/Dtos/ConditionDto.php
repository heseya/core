<?php

namespace App\Dtos;

use Heseya\Dto\Dto;
use Illuminate\Support\Arr;

abstract class ConditionDto extends Dto
{
    protected string $type;

    abstract public static function fromArray(array $array): self;

    public function getType(): string
    {
        return $this->type;
    }

    public function toArray(): array
    {
        return Arr::except(parent::toArray(), 'type');
    }
}
