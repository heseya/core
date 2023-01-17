<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class PaymentStatus extends Enum
{
    public const PENDING = 'pending';
    public const FAILED = 'failed';
    public const SUCCESSFUL = 'successful';
}
