<?php

declare(strict_types=1);

namespace Domain\SalesChannel\Dtos;

use App\Rules\SalesChannelDefault;
use App\Rules\Translations;
use Domain\SalesChannel\Enums\SalesChannelActivityType;
use Domain\SalesChannel\Enums\SalesChannelStatus;
use Spatie\LaravelData\Attributes\Validation\AlphaDash;
use Spatie\LaravelData\Attributes\Validation\Exists;
use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Attributes\Validation\Rule;
use Spatie\LaravelData\Attributes\Validation\Uuid;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\Support\Validation\ValidationContext;

final class SalesChannelCreateDto extends Data
{
    /**
     * @param array<string, array<string, string>> $translations
     * @param Optional|string[] $shipping_method_ids
     * @param Optional|string[] $payment_method_ids
     * @param string[] $published
     */
    public function __construct(
        #[Uuid]
        public readonly Optional|string $id,
        #[Rule(new Translations(['name']))]
        public readonly array $translations,
        #[AlphaDash]
        public readonly string $slug,
        public readonly SalesChannelStatus $status,
        public readonly SalesChannelActivityType $activity,
        #[Uuid]
        public readonly string $price_map_id,
        #[Uuid, Exists('languages', 'id')]
        public readonly string $language_id,
        public readonly array|Optional $shipping_method_ids,
        public readonly array|Optional $payment_method_ids,
        #[Rule(new SalesChannelDefault())]
        public readonly bool $default,

        // TODO: remove temp field
        #[Numeric]
        public readonly string $vat_rate,
        public readonly array|Optional $published,
    ) {}

    /**
     * @return array<string, string[]>
     */
    public static function rules(ValidationContext $context): array
    {
        return [
            'translations.*.name' => ['required', 'string', 'max:100'],
            'shipping_method_ids.*' => ['uuid', 'exists:shipping_methods,id'],
            'payment_method_ids.*' => ['uuid', 'exists:payment_methods,id'],
        ];
    }
}
