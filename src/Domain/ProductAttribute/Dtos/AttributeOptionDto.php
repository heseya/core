<?php

declare(strict_types=1);

namespace Domain\ProductAttribute\Dtos;

use App\Rules\Translations;
use Domain\Metadata\Dtos\MetadataUpdateDto;
use Domain\ProductAttribute\Enums\AttributeTypeValues;
use Domain\ProductAttribute\Models\Attribute;
use Spatie\LaravelData\Attributes\Computed;
use Spatie\LaravelData\Attributes\FromRouteParameter;
use Spatie\LaravelData\Attributes\FromRouteParameterProperty;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Attributes\Validation\Regex;
use Spatie\LaravelData\Attributes\Validation\RequiredIf;
use Spatie\LaravelData\Attributes\Validation\Rule;
use Spatie\LaravelData\Attributes\Validation\Uuid;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;
use Support\Utils\Map;

final class AttributeOptionDto extends Data
{
    /** @var Optional|MetadataUpdateDto[] */
    #[Computed]
    #[MapOutputName('metadata')]
    public readonly array|Optional $metadata_computed;

    /**
     * @param array<string, array<string, string>> $translations
     * @param string[]|Optional $metadata_public
     * @param string[]|Optional $metadata_private
     */
    public function __construct(
        #[Uuid]
        public Optional|string $id,
        #[Rule([new Translations(['name'])]), RequiredIf('attribute.type', [AttributeTypeValues::SINGLE_OPTION, AttributeTypeValues::MULTI_CHOICE_OPTION])]
        public readonly ?array $translations,
        #[Regex('/^\d{1,6}(\.\d{1,2}|)$/')]
        public readonly float|Optional|null $value_number,
        public readonly Optional|string|null $value_date,
        #[MapInputName('metadata')]
        public readonly array|Optional $metadata_public,
        public readonly array|Optional $metadata_private,
        #[FromRouteParameterProperty('attribute', 'id')]
        public readonly Optional|string $attribute_id,
        #[FromRouteParameter('attribute')]
        public readonly Attribute|Optional|null $attribute,
    ) {
        $this->metadata_computed = Map::toMetadata(
            $this->metadata_public,
            $this->metadata_private,
        );
    }
}
