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

final class SalesChannelCreateDto extends Data
{
    /**
     * @param array<string, array<string, string>> $translations
     * @param string[] $countries
     */
    public function __construct(
        #[Uuid]
        public readonly Optional|string $id,
        #[Rule(new Translations(['name']))]
        public readonly array $translations,
        #[AlphaDash]
        public readonly string $slug,
        public readonly Status $status,
        public readonly bool $countries_block_list,
        public readonly string $default_currency,
        #[Uuid, Exists('languages', 'id')]
        public readonly string $default_language_id,

        public readonly array $countries,

        // TODO: remove temp field
        #[Numeric]
        public readonly string $vat_rate,
    ) {}

    /**
     * @return array<string, string[]>
     */
    public static function rules(ValidationContext $context): array
    {
        return [
            'translations.*.name' => ['required', 'string', 'max:100'],
            'countries.*' => ['string', 'exists:countries,code'],
        ];
    }
}
