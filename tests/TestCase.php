<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication, RefreshDatabase;

    public User $user;
    public string $password = 'secret';

    public function setUp(): void
    {
        parent::setUp();

        Artisan::call('passport:install');

        $this->user = User::factory()->create([
            'password' => Hash::make($this->password),
        ]);
    }
}
