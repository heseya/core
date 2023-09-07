<?php

declare(strict_types=1);

namespace Domain\Product\Enums;

use App\Enums\Traits\EnumTrait;

enum ProductSalesChannelStatus: string
{
    use EnumTrait;

    case DISABLED = 'disabled';
    case HIDDEN = 'hidden';
    case PUBLIC = 'public';
}
