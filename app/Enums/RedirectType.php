<?php

namespace App\Enums;

enum RedirectType: int
{
    case PERMANENT = 308;
    case TEMPORARY = 307;
}
