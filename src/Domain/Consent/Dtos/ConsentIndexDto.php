<?php

declare(strict_types=1);

namespace Domain\Consent\Dtos;

use Domain\Consent\Enums\ConsentType;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\EnumCast;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

final class ConsentIndexDto extends Data
{
    public function __construct(
        #[WithCast(EnumCast::class, ConsentType::class)]
        public readonly ConsentType|Optional $type,
    ) {}
}
