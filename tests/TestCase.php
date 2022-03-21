<?php

namespace Tests;

use App\Enums\RoleType;
use App\Enums\TokenType;
use App\Models\App as Application;
use App\Models\Role;
use App\Models\User;
use App\Services\Contracts\TokenServiceContract;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\Traits\JsonQueryCounter;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication, RefreshDatabase, JsonQueryCounter;

    public User $user;
    public Application $application;

    public string $password = 'secret';
    public TokenServiceContract $tokenService;

    public function setUp(): void
    {
        parent::setUp();
        ini_set('memory_limit', '1024M');

        $this->tokenService = App::make(TokenServiceContract::class);

        Role::where('type', RoleType::UNAUTHENTICATED)
            ->firstOrFail()
            ->syncPermissions([]);

        $this->user = User::factory()->create([
            'password' => Hash::make($this->password),
        ]);

        $this->application = Application::factory()->create();
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
}
