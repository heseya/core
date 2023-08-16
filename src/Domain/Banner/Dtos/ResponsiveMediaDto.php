<?php

declare(strict_types=1);

namespace Domain\Banner\Dtos;

use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\Uuid;
use Spatie\LaravelData\Data;

final class ResponsiveMediaDto extends Data
{
    public function __construct(
        public readonly int $min_screen_width,
        #[Uuid, Exists('media', 'id')]
        public readonly string $media,
    ) {}
}
