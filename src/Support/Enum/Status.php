<?php

declare(strict_types=1);

namespace Support\Enum;

enum Status: string
{
    case INACTIVE = 'inactive';
    case ACTIVE = 'active';
    case HIDDEN = 'hidden';
}
