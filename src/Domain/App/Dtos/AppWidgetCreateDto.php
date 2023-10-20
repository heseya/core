<?php

declare(strict_types=1);

namespace Domain\App\Dtos;

use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Url;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

final class AppWidgetCreateDto extends Data
{
    /**
     * @param string[]|Optional $permissions
     */
    public function __construct(
        #[Max(255)]
        public readonly string $name,
        #[Url]
        public readonly string $url,
        #[Max(255)]
        public readonly string $section,
        public readonly array|Optional $permissions,
    ) {}

    /**
     * @return array<string, string[]>
     */
    public static function rules(): array
    {
        return [
            'permissions.*' => ['string'],
        ];
    }
}
