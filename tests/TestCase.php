<?php

namespace Tests;

use App\Enums\RoleType;
use App\Enums\TokenType;
use App\Models\App as Application;
use App\Models\Language;
use App\Models\Role;
use App\Models\User;
use App\Services\Contracts\TokenServiceContract;
use Database\Seeders\InitSeeder;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\Traits\JsonQueryCounter;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    use JsonQueryCounter;
//    use RefreshDatabase;

    public User $user;
    public Application $application;

    public string $password = 'secret';
    public TokenServiceContract $tokenService;

    public string $lang;

    public function setUp(): void
    {
        parent::setUp();

        $seeder = new InitSeeder();
        $seeder->run();

        $this->lang = Language::query()->where('default', true)->firstOrFail()->getKey();
        App::setLocale($this->lang);

        $this->tokenService = App::make(TokenServiceContract::class);

        Role::query()
            ->where('type', RoleType::UNAUTHENTICATED)
            ->firstOrFail()
            ->syncPermissions([]);

        $this->user = User::factory()->create([
            'password' => Hash::make($this->password),
        ]);

        $this->application = Application::factory()->create();
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
            new TokenType(TokenType::ACCESS),
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

    public static function booleanProvider(): array
    {
        return [
            'as user true' => ['user', true, true],
            'as application true' => ['application', true, true],
            'as user false' => ['user', false, false],
            'as application false' => ['application', false, false],
        ];
    }

    public static function couponOrSaleProvider(): array
    {
        return [
            'coupons' => ['coupons'],
            'sales' => ['sales'],
        ];
    }

    public static function authWithDiscountProvider(): array
    {
        return [
            'as user coupons' => ['user', 'coupons'],
            'as user sales' => ['user', 'sales'],
            'as app coupons' => ['application', 'coupons'],
            'as app sales' => ['application', 'sales'],
        ];
    }
}
