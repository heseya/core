<?php

declare(strict_types=1);

namespace Domain\PaymentMethods\Enums;

use App\Enums\Traits\EnumTrait;

enum PaymentMethodType: string
{
    use EnumTrait;

    case PREPAID = 'prepaid';
    case POSTPAID = 'postpaid';
}
