<?php

declare(strict_types=1);

namespace Domain\SalesChannel\Enums;

use App\Enums\Traits\EnumTrait;

enum SalesChannelActivityType: string
{
    use EnumTrait;

    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
}
