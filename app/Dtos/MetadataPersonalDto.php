<?php

namespace App\Dtos;

use App\Enums\MetadataType;
use Heseya\Dto\Dto;

class MetadataPersonalDto extends Dto
{
    private string $name;
    private bool|float|int|string|null $value;
    private string $value_type;

    public static function manualInit(string $name, bool|float|int|string|null $value): self
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

    public function getValue(): bool|float|int|string|null
    {
        return $this->value;
    }

    public function getValueType(): string
    {
        return $this->value_type;
    }
}
