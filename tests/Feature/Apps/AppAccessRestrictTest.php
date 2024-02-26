<?php

namespace Tests\Feature\Apps;

use Domain\App\Models\App;
use Tests\TestCase;

class AppAccessRestrictTest extends TestCase
{
    public static function provider(): array
    {
        return [
            'login' => ['POST', '/login'],
            'logout' => ['POST', '/auth/logout'],
            'request password reset' => ['POST', '/users/reset-password'],
            'validate password reset' => ['GET', '/users/reset-password/uuid/test@example.com'],
            'save password reset' => ['PUT', '/users/save-reset-password'],
            'change password' => ['PUT', '/users/password'],
        ];
    }

    /**
     * @dataProvider provider
     */
    public function testAccessRestrict($method, $url): void
    {
        $application = App::factory()->create();

        $this
            ->actingAs($application)
            ->json($method, $url)
            ->assertStatus(400);
    }
}
