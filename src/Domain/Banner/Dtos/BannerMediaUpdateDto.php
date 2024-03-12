<?php

declare(strict_types=1);

namespace Domain\Banner\Dtos;

use App\Rules\Translations;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Optional;

final class BannerMediaUpdateDto extends Data
{
    /**
     * @param array<string, array<string, string>> $translations
     * @param DataCollection<int, ResponsiveMediaDto>|Optional $media
     * @param string[]|Optional $published
     */
    public function __construct(
        #[Exists('banner_media', 'id')]
        public readonly Optional|string|null $id,
        #[Max(255)]
        public readonly string|null $url,
        #[Rule(new Translations(['title', 'subtitle']))]
        public readonly array|Optional $translations,
        #[DataCollectionOf(ResponsiveMediaDto::class)]
        public readonly DataCollection|Optional $media,
        public readonly array|Optional $published,
    ) {}
}
