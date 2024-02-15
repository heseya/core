<?php

declare(strict_types=1);

namespace Domain\ProductAttribute\Dtos;

use App\Rules\Translations;
use Domain\Metadata\Dtos\MetadataUpdateDto;
use Domain\ProductAttribute\Enums\AttributeTypeValues;
use Domain\ProductAttribute\Models\Attribute;
use Illuminate\Validation\Rule as FacadeRule;
use Spatie\LaravelData\Attributes\Computed;
use Spatie\LaravelData\Attributes\FromRouteParameter;
use Spatie\LaravelData\Attributes\FromRouteParameterProperty;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Attributes\Validation\Uuid;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\Support\Validation\ValidationContext;
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
        public readonly ?array $translations,
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

    /**
     * @return array<string, mixed>
     */
    public static function rules(ValidationContext $context): array
    {
        $attribute = request()->attribute;

        return [
            'value_number' => ['nullable', 'min: 0', 'max:999999.9999', 'decimal:0,4', FacadeRule::requiredIf($attribute->type->is(AttributeTypeValues::NUMBER))],
            'value_date' => ['nullable', FacadeRule::requiredIf($attribute->type->is(AttributeTypeValues::DATE))],
            'translations' => [new Translations(['name']), FacadeRule::requiredIf($attribute->type->is(AttributeTypeValues::SINGLE_OPTION) || $attribute->type->is(AttributeTypeValues::MULTI_CHOICE_OPTION))],
        ];
    }
}
