<?php

namespace Domain\Redirect\Enums;

enum RedirectType: int
{
    case PERMANENT = 308;
    case TEMPORARY = 307;
}
