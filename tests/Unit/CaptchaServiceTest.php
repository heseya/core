<?php

namespace Tests\Unit;

use Domain\Captcha\GoogleRecaptchaV3CaptchaProvider;
use Domain\Setting\Models\Setting;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CaptchaServiceTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    /**
     * @dataProvider captchaDataProvider
     */
    public function testValidateTokenLogic(
        float $google_score,
        string $db_min_score,
        bool $expected_result
    ): void {
        Config::set('captcha.google_recaptcha_url', 'www.google.com/recaptcha/api/siteverify');
        Config::set('captcha.google_recaptcha_secret', 'secret');

        Setting::query()->where('name', 'google_recaptcha_min_score')
            ->firstOrCreate([
            'name' => 'google_recaptcha_min_score',
            'value' => $db_min_score,
                'public' => false,
        ]);

        Http::fake([
            'www.google.com/recaptcha/api/siteverify' => Http::response([
                'success' => true,
                'action' => 'login',
                'score' => $google_score,
            ], 200),
        ]);

        /** @var GoogleRecaptchaV3CaptchaProvider $captchaProvider */
        $captchaProvider = App::make(GoogleRecaptchaV3CaptchaProvider::class);

        $result = $captchaProvider->validate_token('test-token', 'login');

        $this->assertEquals($expected_result, $result);
    }

    public static function captchaDataProvider(): array
    {
        return [
            ['google_score' => 0.9, 'db_min_score' => '0.5', 'expected' => true],
            ['google_score' => 0.1, 'db_min_score' => '0.5', 'expected' => false],
            ['google_score' => 0.5, 'db_min_score' => '0.5', 'expected' => true],

            ['google_score' => 0.3, 'db_min_score' => '0,5', 'expected' => false],

            ['google_score' => 0.0, 'db_min_score' => '',    'expected' => false],
            // Default value is 0.1
            ['google_score' => 0.2, 'db_min_score' => '',    'expected' => true],
        ];
    }
}
