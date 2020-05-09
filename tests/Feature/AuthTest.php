<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AuthTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
    }

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testLogin()
    {
        $response = $this->post('/login', [
            'email' => $this->user->email,
            'password' => $this->password,
        ]);

        $response
            ->assertStatus(200)
            ->assertJsonStructure(['data' => [
                'token',
                'user' => [
                    'id',
                    'email',
                    'name',
                    'avatar',
                ],
            ]]);
    }
}
