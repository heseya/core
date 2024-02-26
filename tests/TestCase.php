<?php

namespace Tests;

use App\Enums\RoleType;
use App\Enums\TokenType;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\InitSeeder;
use Domain\App\Models\App as Application;
use Domain\Auth\Services\TokenService;
use Domain\Language\Language;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\Traits\JsonQueryCounter;
use TRegx\PhpUnit\DataProviders\DataProvider;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    use JsonQueryCounter;
    use RefreshDatabase;

    public User $user;
    public Application $application;

    public string $password = 'secret';
    public TokenService $tokenService;

    public string $lang;

    public function setUp(): void
    {
        parent::setUp();

        $seeder = new InitSeeder();
        $seeder->run();

        $this->lang = Language::query()->where('default', true)->firstOrFail()->getKey();
        App::setLocale($this->lang);

        $this->tokenService = App::make(TokenService::class);

        Role::query()
            ->where('type', RoleType::UNAUTHENTICATED)
            ->firstOrFail()
            ->syncPermissions([]);

        $this->user = User::factory()->create([
            'password' => Hash::make($this->password),
        ]);

        $this->application = Application::factory()->create();

        $this->withHeaders([
            'Accept-Language' => null,
        ]);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        app()->forgetInstances();
    }

    public function actingAs(Authenticatable $user, $guard = null): self
    {
        $token = $this->tokenService->createToken(
            $user,
            TokenType::ACCESS,
            Str::uuid()->toString(),
        );

        $this->withHeaders(
            $this->defaultHeaders + ['Authorization' => "Bearer {$token}"],
        );

        return $this;
    }

    public static function authProvider(): array
    {
        return [
            'as user' => ['user'],
            'as app' => ['application'],
        ];
    }

    public static function booleanProvider(): iterable
    {
        return DataProvider::list(true, false);
    }

    public static function couponOrSaleProvider(): array
    {
        return [
            'coupons' => ['coupons'],
            'sales' => ['sales'],
        ];
    }

    public static function authWithDiscountProvider(): DataProvider
    {
        return DataProvider::cross(DataProvider::of(self::authProvider()), DataProvider::of(self::couponOrSaleProvider()));
    }

    public static function authWithBooleanProvider(): DataProvider
    {
        return DataProvider::cross(DataProvider::of(self::authProvider()), self::booleanProvider());
    }

    public static function authWithTwoBooleansProvider(): DataProvider
    {
        return DataProvider::cross(DataProvider::of(self::authProvider()), DataProvider::zip(self::booleanProvider(), self::booleanProvider()));
    }
}
