<?php

declare(strict_types=1);

namespace Domain\PaymentMethods\Dtos;

use Domain\PaymentMethods\Enums\PaymentMethodType;
use Spatie\LaravelData\Attributes\Validation\Enum;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Url;
use Spatie\LaravelData\Data;

final class PaymentMethodCreateDto extends Data
{
    public function __construct(
        #[Max(255)]
        public readonly string $name,
        #[Max(255)]
        public readonly string $icon,
        #[Url]
        public readonly string $url,
        public readonly bool $public,
        #[Enum(PaymentMethodType::class)]
        public readonly PaymentMethodType $type,
        public readonly bool $creates_default_payment,
    ) {}
}
