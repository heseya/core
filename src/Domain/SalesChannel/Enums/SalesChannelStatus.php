<?php

declare(strict_types=1);

namespace Domain\SalesChannel\Enums;

use App\Enums\Traits\EnumTrait;

enum SalesChannelStatus: string
{
    use EnumTrait;

    case PUBLIC = 'public';
    case PRIVATE = 'private';
}
