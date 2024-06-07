<?php

declare(strict_types=1);

namespace Domain\Captcha;

interface CaptchaProvider
{
    public function validate_token(string $captcha_token, string $validated_action): bool;
}
