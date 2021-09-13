<?php

namespace Tests;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication, RefreshDatabase;

    public User $user;
    public string $password = 'secret';

    public function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
        Role::query()->delete();

        $this->user = User::factory()->create([
            'password' => Hash::make($this->password),
        ]);
    }
    public function actingAs(Authenticatable $user, $guard = null)
    {
        $token = Auth::claims(['typ' => 'access'])->login($user);

        $this->withHeaders(
            $this->defaultHeaders + ['Authorization' => 'Bearer ' . $token],
        );

        return $this;
    }

}
