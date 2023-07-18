<?php

namespace App\DTO\Schemas;

use App\Rules\Translations;
use Spatie\LaravelData\Attributes\Validation\Rule;
use Spatie\LaravelData\Attributes\Validation\Uuid;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\Support\Validation\ValidationContext;

class OptionDto extends Data
{
    public function __construct(
        #[Uuid]
        public readonly Optional|string $id,
        #[Rule(new Translations(['name']))]
        public readonly array $translations,
        public readonly float $price,
        public readonly bool $disabled,
        public readonly array|Optional $items,
    ) {}

    public static function rules(ValidationContext $context): array
    {
        return [
            'items.*' => ['uuid', 'exists:items,id'],
        ];
    }
}
