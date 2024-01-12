<?php

declare(strict_types=1);

namespace Domain\Tag\Dtos;

use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Uuid;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

final class TagUpdateDto extends Data
{
    /**
     * @param array<string, array<string, string>> $translations
     * @param string[]|Optional $published
     */
    public function __construct(
        #[Uuid]
        public readonly Optional|string $id,
        #[Max(6)]
        public readonly Optional|string|null $color,
        public readonly array|Optional $translations,
        public readonly array|Optional $published,
    ) {}
}
