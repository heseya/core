<?php

namespace Tests;

use App\Enums\RoleType;
use App\Enums\TokenType;
use App\Models\Role;
use App\Models\User;
use App\Services\Contracts\TokenServiceContract;
use Database\Seeders\PermissionSeeder;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication, RefreshDatabase;

    public User $user;
    public string $password = 'secret';
    public TokenServiceContract $tokenService;

    public function setUp(): void
    {
        parent::setUp();
        ini_set('memory_limit', '1024M');

        $this->tokenService = App::make(TokenServiceContract::class);

        $this->seed(PermissionSeeder::class);
        Role::where('type', '!=', RoleType::OWNER)->delete();

        $this->user = User::factory()->create([
            'password' => Hash::make($this->password),
        ]);
    }
    public function actingAs(Authenticatable $user, $guard = null)
    {
        $token = $this->tokenService->createToken(
            $user,
            new TokenType(TokenType::ACCESS),
            Str::uuid()->toString(),
        );

        $this->withHeaders(
            $this->defaultHeaders + ['Authorization' => 'Bearer ' . $token],
        );

        return $this;
    }

}
