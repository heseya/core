<?php

namespace App\DTO\Metadata;

use Spatie\LaravelData\Data;

class MetadataDto extends Data
{
    public function __construct(
        public string $name,
        public bool|float|int|string|null $value,
        public bool $public,
        public string $value_type,
    ) {}
}
