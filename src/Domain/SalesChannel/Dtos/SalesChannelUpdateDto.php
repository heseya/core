<?php

declare(strict_types=1);

namespace Domain\SalesChannel\Dtos;

use App\Rules\Translations;
use Spatie\LaravelData\Attributes\Validation\AlphaDash;
use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Attributes\Validation\Rule;
use Spatie\LaravelData\Attributes\Validation\Uuid;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\Support\Validation\ValidationContext;
use Support\Enum\Status;

final class SalesChannelUpdateDto extends Data
{
    /**
     * @param array<string, array<string, string>> $translations
     * @param string[]|Optional $countries
     */
    public function __construct(
        #[Rule(new Translations(['name']))]
        public readonly array|Optional $translations,
        #[AlphaDash]
        public readonly Optional|string $slug,
        public readonly Optional|Status $status,
        public readonly bool|Optional $countries_block_list,
        public readonly Optional|string $default_currency,
        #[Uuid, Exists('languages', 'id')]
        public readonly Optional|string $default_language_id,

        public readonly array|Optional $countries,

        // TODO: remove temp field
        #[Numeric]
        public readonly Optional|string $vat_rate,
    ) {}

    /**
     * @return array<string, string[]>
     */
    public static function rules(ValidationContext $context): array
    {
        return [
            'translations.*.name' => ['sometimes', 'string', 'max:100'],
            'countries.*' => ['string', 'exists:countries,code'],
        ];
    }
}
