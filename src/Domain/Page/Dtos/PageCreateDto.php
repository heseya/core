<?php

declare(strict_types=1);

namespace Domain\Page\Dtos;

use App\DTO\SeoMetadata\SeoMetadataDto;
use App\Rules\Translations;
use Domain\Metadata\Dtos\MetadataUpdateDto;
use Spatie\LaravelData\Attributes\Computed;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\Validation\Rule;
use Spatie\LaravelData\Attributes\Validation\Unique;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;
use Support\Utils\Map;

final class PageCreateDto extends Data
{
    /**
     * @var Optional|MetadataUpdateDto[]
     */
    #[Computed]
    public readonly array|Optional $metadata;

    /**
     * @param array<string, array<string, string>> $translations
     * @param array<string, string>|Optional $metadata_public
     * @param array<string, string>|Optional $metadata_private
     */
    public function __construct(
        #[Rule(new Translations(['name', 'content_html']))]
        public readonly array $translations,
        #[Unique('pages')]
        public readonly string $slug,
        public readonly bool $public,

        public readonly Optional|SeoMetadataDto $seo,

        #[MapInputName('metadata')]
        public readonly array|Optional $metadata_public,
        public readonly array|Optional $metadata_private,
    ) {
        $this->metadata = Map::toMetadata(
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
            'translations.*.name' => ['required', 'string', 'max:255'],
            'translations.*.content_html' => ['sometimes', 'string'],
        ];
    }
}
