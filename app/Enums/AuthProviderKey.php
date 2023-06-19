<?php

namespace App\Enums;

enum AuthProviderKey: string
{
    case FACEBOOK = 'facebook';
    case GOOGLE = 'google';
    case APPLE = 'apple';
    case GITHUB = 'github';
    case GITLAB = 'gitlab';
    case BITBUCKET = 'bitbucket';
    case LINKEDIN = 'linkedin';
}
