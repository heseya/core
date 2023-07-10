<?php

namespace App\DTO\OrderStatus;

use App\DTO\Metadata\Metadata;
use App\Rules\Translations;
use Spatie\LaravelData\Attributes\MapInputName;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Attributes\Validation\Rule;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

final class OrderStatusDto extends Data
{
    use Metadata;

    public function __construct(
        #[Rule(new Translations(['name', 'description']))]
        public array $translations,
        #[Max(6)]
        public string $color,
        public bool $cancel,
        public bool $hidden,
        public bool $no_notifications,
        #[MapInputName('metadata')]
        public array|Optional $metadata_public,
        public array|Optional $metadata_private,

        #[Min(1)]
        public array $published
    ) {
        $this->mapMetadata(
            $this->metadata_public,
            $this->metadata_private,
        );
    }

    public static function rules(): array
    {
        return [
            'translations.*.name' => ['string', 'max:60'],
            'translations.*.description' => ['string', 'max:255', 'nullable'],
        ];
    }
}
