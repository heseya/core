<?php

declare(strict_types=1);

namespace Domain\ProductAttribute\Dtos;

use App\Rules\Translations;
use Domain\Metadata\Dtos\MetadataUpdateDto;
use Domain\ProductAttribute\Enums\AttributeType;
use Domain\ProductAttribute\Models\Attribute;
use Spatie\LaravelData\Attributes\Computed;
use Spatie\LaravelData\Attributes\FromRouteParameter;
use Spatie\LaravelData\Attributes\FromRouteParameterProperty;
use Spatie\LaravelData\Attributes\MapInputName;
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
    public readonly array|Optional $metadata;

    /**
     * @param array<string, array<string, string>> $translations
     * @param string[]|Optional $metadata_public
     * @param string[]|Optional $metadata_private
     */
    public function __construct(
        #[Uuid]
        public Optional|string $id,
        #[Rule([new Translations(['name'])]), RequiredIf('attribute.type', [AttributeType::SINGLE_OPTION->value, AttributeType::MULTI_CHOICE_OPTION->value])]
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
        $this->metadata = Map::toMetadata(
            $this->metadata_public,
            $this->metadata_private,
        );
    }
}
