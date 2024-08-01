<?php

declare(strict_types=1);

namespace Domain\Consent\Enums;

use App\Enums\Traits\EnumTrait;

enum ConsentType: string
{
    use EnumTrait;

    case USER = 'user';
    case ORGANIZATION = 'organization';
}
