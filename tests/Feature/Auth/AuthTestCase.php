<?php

namespace Tests\Feature\Auth;

use App\Enums\TFAType;
use App\Models\UserPreference;
use App\Services\Contracts\OneTimeSecurityCodeContract;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

abstract class AuthTestCase extends TestCase
{
    use WithFaker;

    protected string $expectedLog;
    protected OneTimeSecurityCodeContract $oneTimeSecurityCodeService;
    protected array $expected;
    protected string $cipher;
    protected string $webhookKey;

    /**
     * @return array<string, array<int, TFAType|string|null>>
     */
    public static function tfaMethodProvider(): array
    {
        return [
            'as app 2fa' => [TFAType::APP, 'secret'],
            'as email 2fa' => [TFAType::EMAIL, null],
        ];
    }

    public function setUp(): void
    {
        parent::setUp();
        $this->user->preferences()->associate(
            UserPreference::create([
                'failed_login_attempt_alert' => false,
                'new_localization_login_alert' => false,
                'recovery_code_changed_alert' => false,
            ]),
        );
        $this->user->save();

        $this->expectedLog = 'ClientException(code: 422): Invalid credentials at';
        $this->oneTimeSecurityCodeService = \Illuminate\Support\Facades\App::make(OneTimeSecurityCodeContract::class);

        $this->expected = [
            'token',
            'identity_token',
            'refresh_token',
            'user' => [
                'id',
                'email',
                'name',
                'avatar',
                'roles',
                'shipping_addresses',
                'billing_addresses',
                'permissions',
                'created_at',
            ],
        ];

        $this->cipher = Config::get('webhook.cipher');
        $this->webhookKey = Config::get('webhook.key');
    }

    protected function decryptData(string $data): array|false
    {
        $decoded = base64_decode($data);
        $ivLen = openssl_cipher_iv_length($this->cipher);

        if ($ivLen === false) {
            return false;
        }

        $iv = mb_substr($decoded, 0, $ivLen, '8bit');
        $ciphertext = mb_substr($decoded, $ivLen, null, '8bit');
        $decrypted = openssl_decrypt($ciphertext, $this->cipher, $this->webhookKey, OPENSSL_RAW_DATA, $iv);

        if ($decrypted) {
            return json_decode($decrypted, true);
        }

        return false;
    }
}
