<?php

namespace App\Enums;

use App\Enums\Traits\EnumTrait;

enum GoogleCategoriesLang: string
{
    use EnumTrait;

    case EN = 'en-US';
    case PL = 'pl-PL';
}
