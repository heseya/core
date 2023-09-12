<?php

namespace Domain\Redirect\Dtos;

use Domain\Redirect\Enums\RedirectType;
use Spatie\LaravelData\Attributes\Validation\Enum;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Attributes\Validation\Url;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\EnumCast;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

class RedirectUpdateDto extends Data
{
    public function __construct(
        #[StringType, Max(255)]
        public Optional|string $name,
        #[StringType, Max(255)]
        public Optional|string $slug,
        #[Url]
        public Optional|string $url,
        #[WithCast(EnumCast::class)]
        #[Enum(RedirectType::class)]
        public Optional|RedirectType $type,
    ) {}
}
