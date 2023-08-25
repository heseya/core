<?php

declare(strict_types=1);

namespace Domain\Page\Dtos;

use App\Rules\Translations;
use Domain\Metadata\Dtos\MetadataUpdateDto;
use Domain\Seo\Dtos\SeoMetadataDto;
use Spatie\LaravelData\Attributes\Computed;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Attributes\Validation\Rule;
use Spatie\LaravelData\Attributes\Validation\Unique;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\Support\Validation\References\RouteParameterReference;
use Support\Utils\Map;

final class PageUpdateDto extends Data
{
    /**
     * @var Optional|MetadataUpdateDto[]
     */
    #[Computed]
    #[MapOutputName('metadata')]
    public readonly array|Optional $metadata_computed;

    /**
     * @param array<string, array<string, string>>|Optional $translations
     * @param array<string, string>|Optional $metadata_public
     * @param array<string, string>|Optional $metadata_private
     * @param string[] $published
     */
    public function __construct(
        #[Rule(new Translations(['name', 'content_html']))]
        public readonly array|Optional $translations,
        #[Unique('pages', ignore: new RouteParameterReference('page.id'), ignoreColumn: 'id')]
        public readonly Optional|string $slug,
        public readonly bool|Optional $public,

        public readonly Optional|SeoMetadataDto|null $seo,

        #[MapInputName('metadata')]
        public readonly array|Optional $metadata_public,
        public readonly array|Optional $metadata_private,
        public readonly array|Optional $published,
    ) {
        $this->metadata_computed = Map::toMetadata(
            $this->metadata_public,
            $this->metadata_private,
        );
    }

    /**
     * @return array<string, string[]>
     */
    public static function rules(): array
    {
        return [
            'translations.*.name' => ['sometimes', 'string', 'max:255'],
            'translations.*.content_html' => ['sometimes', 'string'],
        ];
    }
}
