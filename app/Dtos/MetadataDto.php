<?php

namespace App\Dtos;

use App\Enums\MetadataType;
use Heseya\Dto\Dto;

class MetadataDto extends Dto
{
    private string $name;
    private bool|float|int|string|null $value;
    private bool $public;
    private string $value_type;

    public static function manualInit(string $name, bool|float|int|string|null $value, bool $public): self
    {
        return new self(
            name: $name,
            value: $value,
            value_type: MetadataType::matchType($value),
            public: $public,
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

    public function isPublic(): bool
    {
        return $this->public;
    }

    public function getValueType(): string
    {
        return $this->value_type;
    }
}
