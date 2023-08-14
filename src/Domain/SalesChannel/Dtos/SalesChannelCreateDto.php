<?php

declare(strict_types=1);

namespace Domain\SalesChannel\Dtos;

use App\Rules\Translations;
use Brick\Math\BigDecimal;
use Spatie\LaravelData\Attributes\Validation\AlphaDash;
use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Attributes\Validation\Rule;
use Spatie\LaravelData\Attributes\Validation\Uuid;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;
use Support\Enum\Status;

final class SalesChannelCreateDto extends Data
{
    /**
     * @param array<string, array<string, string>> $translations
     */
    public function __construct(
        #[Uuid]
        public readonly Optional|string $id,
        #[Rule(new Translations(['name']))]
        public readonly array $translations,
        #[AlphaDash]
        public readonly string $slug,
        public readonly Status $status,

        // TODO: remove temp field
        #[Numeric]
        public readonly BigDecimal $vat_rate,
    ) {}
}
