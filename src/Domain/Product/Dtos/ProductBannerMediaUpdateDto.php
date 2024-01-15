<?php

declare(strict_types=1);

namespace Domain\Product\Dtos;

use App\Rules\Translations;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Optional;

final class ProductBannerMediaUpdateDto extends Data
{
    /**
     * @param array<string, array<string, string>> $translations
     * @param DataCollection<int, ProductBannerResponsiveMediaDto>|Optional $media
     */
    public function __construct(
        #[Max(255)]
        public readonly string|null $url,
        #[Rule(new Translations(['title', 'subtitle']))]
        public readonly array|Optional $translations,
        #[DataCollectionOf(ProductBannerResponsiveMediaDto::class)]
        public readonly DataCollection|Optional $media,
    ) {}
}
