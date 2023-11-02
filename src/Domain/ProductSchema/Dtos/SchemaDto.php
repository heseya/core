<?php

declare(strict_types=1);

namespace Domain\ProductSchema\Dtos;

use App\Rules\Translations;
use Domain\Metadata\Dtos\MetadataUpdateDto;
use Spatie\LaravelData\Attributes\Computed;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\MapOutputName;
use Spatie\LaravelData\Attributes\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Optional;
use Support\Utils\Map;

/**
 * @property MetadataUpdateDto[]|Optional $metadata_computed
 */
abstract class SchemaDto extends Data
{
    /**
     * @var MetadataUpdateDto[]|Optional $metadata_computed
     */
    #[Computed]
    #[MapOutputName('metadata')]
    public array|Optional $metadata_computed;

    /**
     * @param string[]|Optional $used_schemas
     * @param DataCollection<int,OptionDto>|Optional $options
     * @param string[]|Optional $metadata_public
     * @param string[]|Optional $metadata_private
     * @param array<string,string[]>|Optional $translations
     * @param array<int, string> $published
     */
    public function __construct(
        public bool|Optional $hidden,
        public bool|Optional $required,
        public float|Optional|null $min,
        public float|Optional|null $max,
        public float|Optional|null $step,
        public Optional|string|null $default,
        public array|Optional|null $used_schemas,
        #[DataCollectionOf(OptionDto::class)]
        public DataCollection|Optional $options,
        #[MapInputName('metadata')]
        public readonly array|Optional $metadata_public,
        public readonly array|Optional $metadata_private,
        #[Rule(['sometimes', new Translations(['name'])])]
        public array|Optional $translations = [],
        public array|Optional $published = [],
    ) {
        $this->metadata_computed = Map::toMetadata($metadata_public, $metadata_private);
    }
}
