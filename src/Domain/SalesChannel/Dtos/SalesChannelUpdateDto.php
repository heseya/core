<?php

declare(strict_types=1);

namespace Domain\SalesChannel\Dtos;

use App\Rules\Translations;
use Spatie\LaravelData\Attributes\Validation\AlphaDash;
use Spatie\LaravelData\Attributes\Validation\Numeric;
use Spatie\LaravelData\Attributes\Validation\Rule;
use Spatie\LaravelData\Attributes\Validation\Uuid;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;
use Support\Enum\Status;

final class SalesChannelUpdateDto extends Data
{
    /**
     * @param array<string, array<string, string>> $translations
     */
    public function __construct(
        #[Uuid]
        public readonly Optional|string $id,
        #[Rule(new Translations(['name']))]
        public readonly array|Optional $translations,
        #[AlphaDash]
        public readonly Optional|string $slug,
        public readonly Optional|Status $status,

        // TODO: remove temp field
        #[Numeric]
        public readonly Optional|string $vat_rate,
    ) {}
}
