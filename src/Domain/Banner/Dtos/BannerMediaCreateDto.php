<?php

declare(strict_types=1);

namespace Domain\Banner\Dtos;

use App\Rules\Translations;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Optional;

final class BannerMediaCreateDto extends Data
{
    /**
     * @param array<string, array<string, string>> $translations
     * @param DataCollection<int, ResponsiveMediaDto> $media
     * @param string[] $published
     */
    public function __construct(
        #[Max(255)]
        public readonly Optional|string|null $url,
        #[Rule(new Translations(['title', 'subtitle']))]
        public readonly array $translations,
        #[DataCollectionOf(ResponsiveMediaDto::class)]
        public readonly DataCollection $media,
        public readonly array $published,
    ) {}
}
