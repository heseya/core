<?php

declare(strict_types=1);

namespace Domain\ProductAttribute\Dtos;

use App\Rules\Translations;
use Domain\Metadata\Dtos\MetadataUpdateDto;
use Domain\ProductAttribute\Enums\AttributeType;
use Spatie\LaravelData\Attributes\Computed;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Attributes\Validation\AlphaDash;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Rule;
use Spatie\LaravelData\Attributes\Validation\Unique;
use Spatie\LaravelData\Attributes\Validation\Uuid;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\EnumCast;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\Support\Validation\ValidationContext;
use Support\Utils\Map;

final class AttributeCreateDto extends Data
{
    /** @var Optional|MetadataUpdateDto[] */
    #[Computed]
    #[MapOutputName('metadata')]
    public readonly array|Optional $metadata_computed;

    /**
     * @param array<string, array<string, string>> $translations
     * @param string[] $published
     * @param string[]|Optional $metadata_public
     * @param string[]|Optional $metadata_private
     */
    public function __construct(
        #[Uuid]
        public readonly Optional|string $id,
        #[Rule(new Translations(['name', 'description']))]
        public readonly array $translations,
        #[Max(255), AlphaDash, Unique('attributes')]
        public readonly string $slug,
        #[WithCast(EnumCast::class, AttributeType::class)]
        public readonly AttributeType $type,
        public readonly bool $global,
        public readonly bool $sortable,
        public readonly bool|Optional $include_in_text_search,
        public readonly array $published,

        #[MapInputName('metadata')]
        public readonly array|Optional $metadata_public,
        public readonly array|Optional $metadata_private,
    ) {
        $this->metadata_computed = Map::toMetadata(
            $this->metadata_public,
            $this->metadata_private,
        );
    }

    /**
     * @return array<string, string[]>
     */
    public static function rules(ValidationContext $context): array
    {
        return [
            'translations.*.name' => ['required', 'string', 'max:255'],
            'translations.*.description' => ['sometimes', 'string', 'max:255'],
        ];
    }
}
