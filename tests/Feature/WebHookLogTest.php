<?php

namespace Tests\Feature;

use App\Models\WebHook;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class WebHookLogTest extends TestCase
{
    public function testIndexUnauthorized(): void
    {
        $response = $this->json('GET', '/webhooks/logs');
        $response->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndex($user): void
    {
        $this->$user->givePermissionTo('webhooks.show_details');

        /** @var WebHook $webHook */
        $webHook = WebHook::factory()->create([
            'creator_id' => $this->$user,
            'model_type' => $this->$user::class,
        ]);

        $logOld = $webHook->logs()->create([
            'triggered_at' => Carbon::yesterday(),
            'status_code' => 400,
            'url' => 'localhost',
        ]);
        $logNew = $webHook->logs()->create([
            'triggered_at' => Carbon::now(),
            'status_code' => 400,
            'url' => 'localhost',
        ]);

        $response = $this
            ->actingAs($this->$user)
            ->json('GET', '/webhooks/logs')
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $this->assertEquals($logNew->getKey(), $response->getData()->data[0]->id);
        $this->assertEquals($logOld->getKey(), $response->getData()->data[1]->id);
    }
}
