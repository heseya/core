<?php

declare(strict_types=1);

namespace Domain\ProductAttribute\Dtos;

use App\Rules\Translations;
use Spatie\LaravelData\Attributes\Validation\AlphaDash;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Rule;
use Spatie\LaravelData\Attributes\Validation\Unique;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\Support\Validation\ValidationContext;

final class AttributeUpdateDto extends Data
{
    /**
     * @param array<string, array<string, string>> $translations
     */
    public function __construct(
        #[Rule(new Translations(['name']))]
        public readonly array $translations,
        #[Max(255)]
        public readonly Optional|string $description,
        #[Max(255), AlphaDash, Unique('attributes')]
        public readonly Optional|string $slug,
        public readonly bool|Optional $global,
        public readonly bool|Optional $sortable,
    ) {}

    /**
     * @return array<string, string[]>
     */
    public static function rules(ValidationContext $context): array
    {
        return [
            'type' => ['prohibited'],
        ];
    }
}
