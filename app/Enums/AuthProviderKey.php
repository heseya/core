<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class AuthProviderKey extends Enum
{
    public const GOOGLE = 'google';
    public const APPLE = 'apple';
    public const FACEBOOK = 'facebook';
    public const TWITTER = 'twitter';
    public const GITHUB = 'github';
    public const GITLAB = 'gitlab';
    public const BITBUCKET = 'bitbucket';
    public const LINKEDIN = 'linkedin';
}
