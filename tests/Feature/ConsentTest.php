<?php

namespace Tests\Feature;

use App\Models\Consent;
use Tests\TestCase;

class ConsentTest extends TestCase
{
    private Consent $consent;

    public function setUp(): void
    {
        parent::setUp();
        $this->consent = Consent::factory()->create();
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexUnauthorized($user): void
    {
        Consent::factory()->count(10)->create();

        $response = $this->actingAs($this->$user)->json('get', '/consents');

        $response->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndex($user): void
    {
        $this->$user->givePermissionTo('consents.show');

        Consent::factory()->count(10)->create();

        $response = $this->actingAs($this->$user)->json('get', '/consents');

        $response->assertOk();
        $response->assertJsonCount(11, 'data');
    }

    /**
     * @dataProvider authProvider
     */
    public function testStoreUnauthorized($user): void
    {
        $consent = Consent::factory()->make();

        $response = $this->actingAs($this->$user)->json('post', '/consents', $consent->toArray());

        $response->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testStore($user): void
    {
        $this->$user->givePermissionTo('consents.add');

        $consent = Consent::factory()->make();

        $response = $this->actingAs($this->$user)->json('post', '/consents', $consent->toArray());

        $response->assertCreated();
        $response->assertJsonFragment($consent->toArray());

        $this->assertDatabaseCount('consents', 2)
            ->assertDatabaseHas('consents', $consent->toArray());
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateUnauthorized($user): void
    {
        $consent = Consent::factory()->make();

        $response = $this->actingAs($this->$user)
            ->json('patch', '/consents/id:' . $this->consent->getKey(), $consent->toArray());

        $response->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testFullUpdate($user): void
    {
        $this->$user->givePermissionTo('consents.edit');

        $consent = Consent::factory()->make();

        $response = $this->actingAs($this->$user)
            ->json('patch', '/consents/id:' . $this->consent->getKey(), $consent->toArray());

        $response->assertOk();
        $response->assertJsonFragment($consent->toArray());

        $this->assertDatabaseCount('consents', 1)
            ->assertDatabaseHas('consents', $consent->toArray());
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdate($user): void
    {
        $this->$user->givePermissionTo('consents.edit');

        $data = ['name' => 'updated'];

        $response = $this->actingAs($this->$user)
            ->json('patch', '/consents/id:' . $this->consent->getKey(), $data);

        $response->assertOk();
        $response->assertJsonFragment([
            'name' => 'updated',
            'description_html' => $this->consent->description_html,
            'required' => $this->consent->required,
        ]);

        $this->assertDatabaseCount('consents', 1)
            ->assertDatabaseHas('consents', [
                'name' => 'updated',
                'description_html' => $this->consent->description_html,
                'required' => $this->consent->required,
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testDeleteUnauthorized($user): void
    {
        $response = $this->actingAs($this->$user)
            ->json('delete', '/consents/id:' . $this->consent->getKey());

        $response->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testDelete($user): void
    {
        $this->$user->givePermissionTo('consents.remove');

        $response = $this->actingAs($this->$user)
            ->json('delete', '/consents/id:' . $this->consent->getKey());

        $response->assertStatus(204);

        $this->assertDatabaseMissing('consents', $this->consent->toArray());
    }
}
