<?php

namespace App\Enums;

use App\Enums\Traits\EnumTrait;

enum AuthProviderKey: string
{
    use EnumTrait;

    case FACEBOOK = 'facebook';
    case GOOGLE = 'google';
    case APPLE = 'apple';
    case GITHUB = 'github';
    case GITLAB = 'gitlab';
    case BITBUCKET = 'bitbucket';
    case LINKEDIN = 'linkedin';
}
