<?php

declare(strict_types=1);

namespace Domain\Banner\Dtos;

use Domain\Metadata\Dtos\MetadataUpdateDto;
use Spatie\LaravelData\Attributes\Computed;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Unique;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Optional;
use Support\Utils\Map;

final class BannerCreateDto extends Data
{
    /**
     * @var Optional|MetadataUpdateDto[]
     */
    #[Computed]
    public readonly array|Optional $metadata;

    /**
     * @param DataCollection<int, BannerMediaCreateDto>|Optional $banner_media
     * @param array<string, string>|Optional $metadata_public
     * @param array<string, string>|Optional $metadata_private
     */
    public function __construct(
        #[Unique('banners'), Max(255)]
        public readonly string $slug,
        #[Max(255)]
        public readonly string $name,
        public readonly bool $active,
        #[DataCollectionOf(BannerMediaCreateDto::class)]
        public readonly DataCollection|Optional $banner_media,

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
