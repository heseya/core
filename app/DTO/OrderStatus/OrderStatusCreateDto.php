<?php

namespace App\DTO\OrderStatus;

use App\Rules\Translations;
use App\Utils\Map;
use Spatie\LaravelData\Attributes\Computed;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

final class OrderStatusCreateDto extends Data
{
    #[Computed]
    public readonly array|Optional $metadata;

    public function __construct(
        #[Rule(new Translations(['name', 'description']))]
        public readonly array $translations,
        #[Max(6)]
        public readonly string $color,
        public readonly bool $cancel,
        public readonly bool $hidden,
        public readonly bool $no_notifications,
        #[Min(1)]
        public readonly array $published,

        #[MapInputName('metadata')]
        public readonly array|Optional $metadata_public,
        public readonly array|Optional $metadata_private,
    ) {
        $this->metadata = Map::toMetadata(
            $this->metadata_public,
            $this->metadata_private,
        );
    }

    public static function rules(): array
    {
        return [
            'translations.*.name' => ['required', 'string', 'max:60'],
            'translations.*.description' => ['nullable', 'string', 'max:255'],
        ];
    }
}
