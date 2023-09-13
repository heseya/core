<?php

namespace Domain\User\Dtos;

use App\Enums\TFAType;
use Spatie\LaravelData\Attributes\Validation\Enum;
use Spatie\LaravelData\Attributes\Validation\Required;
use Spatie\LaravelData\Attributes\WithCast;
use Spatie\LaravelData\Casts\EnumCast;
use Spatie\LaravelData\Data;

class TFASetupDto extends Data
{
    public function __construct(
        #[WithCast(EnumCast::class, TFAType::class)]
        #[Required, Enum(TFAType::class)]
        public TFAType $type,
    ) {
    }
}
