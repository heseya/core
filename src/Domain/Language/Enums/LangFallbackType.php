<?php

namespace Domain\Language\Enums;

use App\Enums\Traits\EnumTrait;

enum LangFallbackType: string
{
    use EnumTrait;
    case NONE = 'none';
    case DEFAULT = 'default';
    case ANY = 'any';
}
