<?php

declare(strict_types=1);

namespace Domain\App\Dtos;

use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Optional;

final class AppConfigDto extends Data
{
    /**
     * @param string[] $required_permissions
     * @param DataCollection<int,InternalPermissionDto> $internal_permissions
     * @param DataCollection<int,AppWidgetCreateDto>|Optional $widgets
     * @param string[]|Optional $optional_permissions
     */
    public function __construct(
        public readonly string $name,
        public readonly string $author,
        public readonly string $version,
        public readonly string $api_version,
        public readonly string|null $description,
        public readonly string|null $microfrontend_url,
        public readonly string|null $icon,
        public readonly bool|null $licence_required,
        public readonly array $required_permissions,
        #[DataCollectionOf(InternalPermissionDto::class)]
        public readonly DataCollection $internal_permissions,
        #[DataCollectionOf(AppWidgetCreateDto::class)]
        public readonly DataCollection|Optional $widgets,
        public readonly array|Optional $optional_permissions = [],
    ) {}

    /**
     * @return array<string, string[]>
     */
    public static function rules(): array
    {
        return [
            'required_permissions.*' => ['string'],
            'optional_permissions.*' => ['string'],
        ];
    }
}
