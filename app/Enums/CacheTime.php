<?php

namespace App\Enums;

enum CacheTime: int
{
    case LONG_TIME = 1800;
    case SHORT_TIME = 600;
    case VERY_SHORT_TIME = 60;
}
