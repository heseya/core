<?php

namespace App\Dtos;

use App\Enums\ConditionType;
use Heseya\Dto\Dto;
use Illuminate\Support\Arr;

abstract class ConditionDto extends Dto
{
    protected ConditionType $type;

    abstract public static function fromArray(array $array): self;

    public function getType(): ConditionType
    {
        return $this->type;
    }

    public function getTypeAsString(): string
    {
        return $this->type->value;
    }

    public function toArray(): array
    {
        return Arr::except(parent::toArray(), 'type');
    }
}
