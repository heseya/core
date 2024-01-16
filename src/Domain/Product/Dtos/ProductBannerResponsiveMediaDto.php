<?php

declare(strict_types=1);

namespace Domain\Product\Dtos;

use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\Uuid;
use Spatie\LaravelData\Data;

final class ProductBannerResponsiveMediaDto extends Data
{
    public function __construct(
        public readonly int $min_screen_width,
        #[Uuid, Exists('media', 'id')]
        public readonly string $media,
    ) {}
}
