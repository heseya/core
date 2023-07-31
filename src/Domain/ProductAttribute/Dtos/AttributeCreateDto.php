<?php

declare(strict_types=1);

namespace Domain\ProductAttribute\Dtos;

use App\DTO\Metadata\MetadataDto;
use Domain\ProductAttribute\Enums\AttributeType;
use Spatie\LaravelData\Attributes\Computed;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\Validation\AlphaDash;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Unique;
use Spatie\LaravelData\Attributes\Validation\Uuid;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;
use Support\Utils\Map;

final class AttributeCreateDto extends Data
{
    /** @var Optional|MetadataDto[] */
    #[Computed]
    public readonly array|Optional $metadata;

    /**
     * @param string[]|Optional $metadata_public
     * @param string[]|Optional $metadata_private
     */
    public function __construct(
        #[Uuid]
        public readonly Optional|string $id,
        #[Max(255)]
        public readonly string $name,
        #[Max(255), AlphaDash, Unique('attributes')]
        public readonly string $slug,
        public readonly AttributeType $type,
        public readonly bool $global,
        public readonly bool $sortable,

        #[MapInputName('metadata')]
        public readonly array|Optional $metadata_public,
        public readonly array|Optional $metadata_private,
    ) {
        $this->metadata = Map::toMetadata(
            $this->metadata_public,
            $this->metadata_private,
        );
    }
}
