<?php

declare(strict_types=1);

namespace Domain\Seo\Dtos;

use Spatie\LaravelData\Attributes\Validation\Uuid;
use Spatie\LaravelData\Data;

final class ExcludedModelDto extends Data
{
    public function __construct(
        #[Uuid]
        public readonly string $id,
    ) {}
}
