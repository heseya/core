<?php

namespace Tests;

use App\Enums\RoleType;
use App\Enums\TokenType;
use App\Models\App as Application;
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
use Tests\Support\ElasticTest;
use Tests\Traits\JsonQueryCounter;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication, RefreshDatabase, JsonQueryCounter, ElasticTest;

    public User $user;
    public Application $application;

    public string $password = 'secret';
    public TokenServiceContract $tokenService;

    public function setUp(): void
    {
        parent::setUp();
        ini_set('memory_limit', '4096M');

        $this->fakeElastic();

        $seeder = new InitSeeder();
        $seeder->run();

        $this->tokenService = App::make(TokenServiceContract::class);

        Role::where('type', RoleType::UNAUTHENTICATED)
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

    public function authProvider(): array
    {
        return [
            'as user' => ['user'],
            'as app' => ['application'],
        ];
    }

    public function actingAs(Authenticatable $authenticatable, $guard = null): self
    {
        $token = $this->tokenService->createToken(
            $authenticatable,
            new TokenType(TokenType::ACCESS),
            Str::uuid()->toString(),
        );

        $this->withHeaders(
            $this->defaultHeaders + ['Authorization' => "Bearer ${token}"],
        );

        return $this;
    }

    public function booleanProvider(): array
    {
        return array_merge($this->trueBooleanProvider(), $this->falseBooleanProvider());
    }

    public function trueBooleanProvider(): array
    {
        return [
            'as user 1' => ['user', 1, true],
            'as user on' => ['user', 'on', true],
            'as user yes' => ['user', 'yes', true],
            'as application true' => ['application', true, true],
        ];
    }

    public function falseBooleanProvider(): array
    {
        return [
            'as user 0' => ['user', 0, false],
            'as user off' => ['user', 'off', false],
            'as user no' => ['user', 'no', false],
            'as application false' => ['application', false, false],
        ];
    }
}
