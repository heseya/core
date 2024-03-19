<?php

declare(strict_types=1);

namespace Domain\Captcha;

use App\Enums\ExceptionsEnums\Exceptions;
use App\Exceptions\ServerException;
use Domain\Setting\Services\Contracts\SettingsServiceContract;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

final class GoogleRecaptchaV3CaptchaProvider implements CaptchaProvider
{
    public function __construct(
        protected SettingsServiceContract $settingsService,
    ) {}

    /**
     * @throws ServerException
     */
    public function validate_token(string $captcha_token, string $validated_action): bool
    {
        $url = Config::get('captcha.google_recaptcha_url');
        $secret = Config::get('captcha.google_recaptcha_secret');

        $response = Http::post($url, [
            'secret' => $secret,
            'response' => $captcha_token,
        ]);

        if (!$response->successful()) {
            throw new ServerException(Exceptions::SERVER_CAPTCHA_ERROR);
        }

        $success = $response->json('success');
        $action = $response->json('action');
        $score = $response->json('score');

        if (!is_bool($success) || !is_string($action) || !is_numeric($score)) {
            throw new ServerException(Exceptions::SERVER_CAPTCHA_ERROR);
        }

        if (!$success) {
            return false;
        }

        if ($action !== $validated_action) {
            return false;
        }

        $min_Score = $this->settingsService->getSetting('google_recaptcha_min_score')->value;

        return !($score < $min_Score);
    }
}
