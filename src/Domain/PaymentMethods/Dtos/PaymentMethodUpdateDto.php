<?php

declare(strict_types=1);

namespace Domain\PaymentMethods\Dtos;

use Domain\PaymentMethods\Enums\PaymentMethodType;
use Spatie\LaravelData\Attributes\Validation\Enum;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Url;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;

final class PaymentMethodUpdateDto extends Data
{
    public function __construct(
        #[Max(255)]
        public readonly Optional|string $name,
        #[Max(255)]
        public readonly Optional|string $icon,
        #[Url]
        public readonly Optional|string $url,
        public readonly bool|Optional $public,
        #[Enum(PaymentMethodType::class)]
        public readonly Optional|PaymentMethodType $type,
        public readonly bool|Optional $creates_default_payment,
    ) {}
}
