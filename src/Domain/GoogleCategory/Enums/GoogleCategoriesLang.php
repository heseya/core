<?php

declare(strict_types=1);

namespace Domain\GoogleCategory\Enums;

use App\Enums\Traits\EnumTrait;

enum GoogleCategoriesLang: string
{
    use EnumTrait;

    case EN = 'en-US';
    case PL = 'pl-PL';
}
