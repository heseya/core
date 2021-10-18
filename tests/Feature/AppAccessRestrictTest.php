<?php

namespace Tests\Feature;

use App\Models\App;
use Tests\TestCase;

class AppAccessRestrictTest extends TestCase
{
    protected App $application;

    public function setUp(): void
    {
        parent::setUp();

        $this->application = App::factory()->create();
    }

    public function provider(): array
    {
        return [
            'login' => ['POST', '/login'],
            'logout' => ['POST', '/auth/logout'],
            'request password reset' => ['POST', '/users/reset-password'],
            'validate password reset' => ['GET', '/users/reset-password/uuid/test@example.com'],
            'save password reset' => ['PATCH', '/users/save-reset-password'],
            'change password' => ['PATCH', '/user/password'],
        ];
    }

    /**
     * @dataProvider provider
     */
    public function testAccessRestrict($method, $url): void
    {
        $this
            ->actingAs($this->application)
            ->json($method, $url)
            ->assertStatus(400);
    }
}
