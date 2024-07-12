<?php

declare(strict_types=1);

namespace Domain\Organization\Enums;

use App\Enums\Traits\EnumTrait;

enum OrganizationStatus: string
{
    use EnumTrait;

    case UNVERIFIED = 'Unverified';
    case VERIFIED = 'Verified';
    case REJECTED = 'Rejected';
}
