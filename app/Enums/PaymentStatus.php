<?php

namespace App\Enums;

use App\Enums\Traits\EnumTrait;

enum PaymentStatus: string
{
    use EnumTrait;

    case PENDING = 'pending';
    case FAILED = 'failed';
    case SUCCESSFUL = 'successful';
}
