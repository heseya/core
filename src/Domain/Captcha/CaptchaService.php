<?php

declare(strict_types=1);

namespace Domain\Captcha;

use App\DTO\Auth\RegisterDto;
use App\Enums\ExceptionsEnums\Exceptions;
use App\Exceptions\ClientException;
use Domain\Setting\Services\Contracts\SettingsServiceContract;
use Spatie\LaravelData\Optional;

final class CaptchaService
{
    public function __construct(
        protected SettingsServiceContract $settingsService,
        protected CaptchaProvider $captchaProvider,
    ) {}

    /**
     * @throws ClientException
     */
    public function validate_registration_captcha(RegisterDto $dto): void
    {
        if (!$this->settingsService->getSetting('enable_captcha')->value) {
            return;
        }

        if ($dto->captcha_token instanceof Optional) {
            throw new ClientException(Exceptions::CLIENT_CAPTCHA_FAILED);
        }

        /** @var string $captcha_token */
        $captcha_token = $dto->captcha_token;

        if (!$this->captchaProvider->validate_token($captcha_token, 'register')) {
            throw new ClientException(Exceptions::CLIENT_CAPTCHA_FAILED);
        }
    }
}
