<?php

declare(strict_types=1);

namespace Domain\Redirect\Dtos;

use Domain\Redirect\Enums\RedirectType;
use Spatie\LaravelData\Attributes\Validation\BooleanType;
use Spatie\LaravelData\Attributes\Validation\Enum;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\EnumCast;
use Spatie\LaravelData\Data;

final class RedirectCreateDto extends Data
{
    public function __construct(
        #[Required, StringType, Max(255)]
        public string $name,
        #[Required, StringType, Max(255)]
        public string $source_url,
        #[Required, StringType, Max(255)]
        public string $target_url,
        #[WithCast(EnumCast::class)]
        #[Required, Enum(RedirectType::class)]
        public RedirectType $type,
        #[Required, BooleanType]
        public bool $enabled = true,
    ) {}
}
