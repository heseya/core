<?php

namespace App\Dtos;

use App\Enums\MetadataType;
use Heseya\Dto\Dto;

class MetadataPersonalDto extends Dto
{
    private string $name;
    private bool|int|float|string|null $value;
    private string $value_type;

    public static function manualInit(string $name, bool|int|float|string|null $value): self
    {
        return new self(
            name: $name,
            value: $value,
            value_type: MetadataType::matchType($value),
        );
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getValue(): bool|int|float|string|null
    {
        return $this->value;
    }

    public function getValueType(): string
    {
        return $this->value_type;
    }
}
