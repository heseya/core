<?php

declare(strict_types=1);

namespace Domain\Redirect\Enums;

enum RedirectType: int
{
    case PERMANENT_REDIRECT = 308;
    case TEMPORARY_REDIRECT = 307;
    case FOUND = 302;
    case MOVED_PERMANENTLY = 301;
}
