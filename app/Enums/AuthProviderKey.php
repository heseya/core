<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class AuthProviderKey extends Enum
{
    public const FACEBOOK = 'facebook';
    public const GOOGLE = 'google';
    public const APPLE = 'apple';
    //    public const TWITTER = 'twitter';
    public const GITHUB = 'github';
    public const GITLAB = 'gitlab';
    public const BITBUCKET = 'bitbucket';
    public const LINKEDIN = 'linkedin';

    public static function getDriver(string $value): string
    {
        return match ($value) {
            //            self::TWITTER => 'twitter-oauth-2',
            default => $value,
        };
    }
}
