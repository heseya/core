<?php

declare(strict_types=1);

namespace Domain\ProductAttribute\Dtos;

use App\Rules\Translations;
use Domain\ProductAttribute\Models\Attribute;
use Spatie\LaravelData\Attributes\Validation\AlphaDash;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Rule;
use Spatie\LaravelData\Attributes\Validation\Unique;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\Support\Validation\References\RouteParameterReference;
use Spatie\LaravelData\Support\Validation\ValidationContext;

final class AttributeUpdateDto extends Data
{
    /**
     * @param array<string, array<string, string>> $translations
     * @param string[] $published
     */
    public function __construct(
        #[Rule(new Translations(['name', 'description']))]
        public readonly array|Optional $translations,
        #[Max(255), AlphaDash, Unique('attributes', ignore: new RouteParameterReference('id'), ignoreColumn: 'id')]
        public readonly Optional|string $slug,
        public readonly bool|Optional $global,
        public readonly bool|Optional $sortable,
        public readonly array|Optional $published,
        public readonly bool|Optional $include_in_text_search,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public static function rules(ValidationContext $context): array
    {
        /** @var Attribute $attribute */
        $attribute = Attribute::query()->where('id', request()->route('id'))->first();
        $type = request()->input('type');

        return [
            'type' => [\Illuminate\Validation\Rule::prohibitedIf($type && $type !== $attribute->type->value)],
            'translations.*.name' => ['sometimes', 'string', 'max:255'],
            'translations.*.description' => ['sometimes', 'string', 'max:255'],
        ];
    }
}
