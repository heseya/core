<?php

namespace Domain\Redirect\Dtos;

use Domain\Redirect\Enums\RedirectType;
use Spatie\LaravelData\Attributes\Validation\Enum;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Attributes\Validation\Url;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\EnumCast;
use Spatie\LaravelData\Data;

class RedirectCreateDto extends Data
{
    public function __construct(
        #[Required, StringType, Max(255)]
        public string $name,
        #[Required, StringType, Max(255)]
        public string $slug,
        #[Required, Url]
        public string $url,
        #[WithCast(EnumCast::class)]
        #[Required, Enum(RedirectType::class)]
        public RedirectType $type,
    ) {
    }
}
