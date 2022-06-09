<?php

namespace Tests\Feature;

use App\Enums\EventType;
use App\Models\WebHook;
use App\Models\WebHookEventLogEntry;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class WebHookLogTest extends TestCase
{
    private WebHook $webHookOne;
    private WebHook $webHookTwo;
    private WebHookEventLogEntry $logOne;
    private WebHookEventLogEntry $logTwo;

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

    /**
     * @dataProvider authProvider
     */
    public function testIndexFilteredByStatusCode($user): void
    {
        $this->$user->givePermissionTo('webhooks.show_details');

        $this->prepareData($user);

        $this
            ->actingAs($this->$user)
            ->json('GET', '/webhooks/logs', [
                'status_code' => 200,
            ])
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['id' => $this->logOne->getKey()]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexFilteredByEvent($user): void
    {
        $this->$user->givePermissionTo('webhooks.show_details');

        $this->prepareData($user);

        $this
            ->actingAs($this->$user)
            ->json('GET', '/webhooks/logs', [
                'event' => EventType::COUPON_CREATED,
            ])
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['id' => $this->logOne->getKey()]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexFilteredByWebHookId($user): void
    {
        $this->$user->givePermissionTo('webhooks.show_details');

        $this->prepareData($user);

        $this
            ->actingAs($this->$user)
            ->json('GET', '/webhooks/logs', [
                'web_hook_id' => $this->webHookOne->getKey(),
            ])
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['id' => $this->logOne->getKey()]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexFilteredSuccessful($user): void
    {
        $this->$user->givePermissionTo('webhooks.show_details');

        $this->prepareData($user);

        $this
            ->actingAs($this->$user)
            ->json('GET', '/webhooks/logs', [
                'successful' => true,
            ])
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['id' => $this->logOne->getKey()]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexFilteredNotSuccessful($user): void
    {
        $this->$user->givePermissionTo('webhooks.show_details');

        $this->prepareData($user);

        $this
            ->actingAs($this->$user)
            ->json('GET', '/webhooks/logs', [
                'successful' => false,
            ])
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment(['id' => $this->logTwo->getKey()]);
    }

    private function prepareData($user): void
    {
        $this->webHookOne = WebHook::factory()->create([
            'creator_id' => $this->$user,
            'model_type' => $this->$user::class,
            'events' => [EventType::COUPON_CREATED],
        ]);

        $this->webHookTwo = WebHook::factory()->create([
            'creator_id' => $this->$user,
            'model_type' => $this->$user::class,
            'events' => [EventType::COUPON_DELETED],
        ]);

        $this->logOne = $this->webHookOne->logs()->create([
            'triggered_at' => Carbon::yesterday(),
            'status_code' => 200,
            'url' => 'localhost',
        ]);

        $this->logTwo = $this->webHookTwo->logs()->create([
            'triggered_at' => Carbon::now(),
            'status_code' => 400,
            'url' => 'localhost',
        ]);

        $this->webHookTwo->logs()->create([
            'triggered_at' => Carbon::now(),
            'status_code' => 400,
            'url' => 'localhost',
        ]);
    }
}
