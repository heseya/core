<?php

declare(strict_types=1);

namespace Domain\Language\Dtos;

use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Unique;
use Spatie\LaravelData\Data;

final class LanguageCreateDto extends Data
{
    public function __construct(
        #[Max(16), Unique('languages')]
        public string $iso,
        #[Max(80)]
        public string $name,
        public bool $default,
        public bool $hidden,
    ) {}
}
