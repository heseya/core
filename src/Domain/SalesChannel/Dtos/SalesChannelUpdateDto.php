<?php

declare(strict_types=1);

namespace Domain\SalesChannel\Dtos;

use App\Rules\SalesChannelActivityOrganization;
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

final class SalesChannelUpdateDto extends Data
{
    /**
     * @param array<string, array<string, string>> $translations
     * @param Optional|string[] $shipping_method_ids
     * @param Optional|string[] $payment_method_ids
     * @param string[] $published
     */
    public function __construct(
        #[Rule(new Translations(['name']))]
        public readonly array|Optional $translations,
        #[AlphaDash]
        public readonly Optional|string $slug,
        public readonly Optional|SalesChannelStatus $status,
        #[Rule(new SalesChannelActivityOrganization())]
        public readonly Optional|SalesChannelActivityType $activity,
        #[Uuid, Exists('languages', 'id')]
        public readonly Optional|string $language_id,
        #[Uuid]
        public readonly Optional|string $price_map_id,
        public readonly array|Optional $shipping_method_ids,
        public readonly array|Optional $payment_method_ids,
        #[Rule(new SalesChannelDefault())]
        public readonly bool $default,

        // TODO: remove temp field
        #[Numeric]
        public readonly Optional|string $vat_rate,
        public readonly array|Optional $published,
    ) {}

    /**
     * @return array<string, string[]>
     */
    public static function rules(ValidationContext $context): array
    {
        return [
            'translations.*.name' => ['sometimes', 'string', 'max:100'],
            'shipping_method_ids.*' => ['uuid', 'exists:shipping_methods,id'],
            'payment_method_ids.*' => ['uuid', 'exists:payment_methods,id'],
        ];
    }
}
