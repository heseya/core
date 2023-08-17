<?php

declare(strict_types=1);

namespace Domain\Language\Dtos;

use Illuminate\Support\Optional;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Unique;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Validation\References\RouteParameterReference;

final class LanguageUpdateDto extends Data
{
    public function __construct(
        #[Max(16), Unique('languages', ignore: new RouteParameterReference('language'))]
        public Optional|string $iso,
        #[Max(80)]
        public Optional|string $name,
        public bool|Optional $default,
        public bool|Optional $hidden,
    ) {}
}