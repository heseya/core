<?php

declare(strict_types=1);

namespace Domain\Seo\Dtos;

use App\Rules\Translations;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;
use Support\Dtos\Traits\FilterTranslationsOnlyExistingLanguages;

final class SeoMetadataCreateDto extends Data
{
    use FilterTranslationsOnlyExistingLanguages;

    /**
     * @param array<string, array<string, string>> $translations
     * @param string[]|Optional|null $header_tags
     * @param string[]|Optional $published
     */
    public function __construct(
        #[Rule(new Translations(['title', 'description', 'keywords', 'no_index']))]
        public readonly array $translations,
        public readonly Optional|string|null $twitter_card,
        #[MapInputName('og_image_id'), Exists('media', 'id')]
        public readonly Optional|string|null $og_image,
        public readonly Optional|string|null $model_id,
        public readonly Optional|string|null $model_type,
        public readonly bool|Optional $no_index,
        public readonly array|Optional|null $header_tags,
        /** @var array<string> */
        public readonly array|Optional $published,
    ) {}
}
