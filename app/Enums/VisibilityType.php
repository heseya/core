<?php

namespace App\Enums;

enum VisibilityType: string
{
    case PUBLIC = 'public';
    case PRIVATE = 'private';

    /*
     * TODO: Implement this visibility type.
     * This visibility type makes resource visible with specific request param.
     */
    //    case HIDDEN = 'hidden';
}
