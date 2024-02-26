<?php

declare(strict_types=1);

namespace Domain\App\Dtos;

use App\Rules\AppUniqueUrl;
use Domain\Metadata\Dtos\MetadataUpdateDto;
use Spatie\LaravelData\Attributes\Computed;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\Validation\Present;
use Spatie\LaravelData\Attributes\Validation\Rule;
use Spatie\LaravelData\Attributes\Validation\Url;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;
use Support\Utils\Map;

final class AppInstallDto extends Data
{
    /**
     * @var Optional|MetadataUpdateDto[]
     */
    #[Computed]
    public readonly array|Optional $metadata;

    /**
     * @param array<int, string> $allowed_permissions
     * @param array<int, string> $public_app_permissions
     * @param array<string, string>|Optional $metadata_public
     * @param array<string, string>|Optional $metadata_private
     */
    public function __construct(
        #[Url, Rule(new AppUniqueUrl())]
        public readonly string $url,
        public readonly string|null $name,
        public readonly string|null $licence_key,
        #[Present]
        public readonly array $allowed_permissions,
        // TODO do sprawdzenia, czy czasem może być bez present jak nie ma nullable ani opcjonal
        #[Present]
        public readonly array $public_app_permissions,

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
