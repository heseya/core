<?php

namespace Tests\Feature\Organizations;

use Domain\PriceMap\PriceMap;
use Tests\TestCase;

class PriceMapTest extends TestCase
{
    private PriceMap $priceMap;

    public function setUp(): void
    {
        parent::setUp();

        $this->priceMap = PriceMap::factory()->create();
    }

    public function testIndexUnauthorized(): void
    {
        $this->json('GET', '/price-maps')->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndex(string $user): void
    {
        $this->{$user}->givePermissionTo('price-maps.show');

        PriceMap::factory()->count(10)->create();

        $response = $this->actingAs($this->{$user})->json('GET', '/price-maps');

        $response->assertOk()->assertJsonCount(12, 'data');
    }

    public function testCreateUnauthorized(): void
    {
        $this
            ->json('POST', '/price-maps', PriceMap::factory()->make()->toArray())
            ->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreate(string $user): void
    {
        $this->{$user}->givePermissionTo('price-maps.add');

        $priceMapData = PriceMap::factory()->make()->toArray();

        $response = $this->actingAs($this->{$user})
            ->json('POST', '/price-maps', $priceMapData);

        $response->assertCreated()
            ->assertJsonFragment([
                'name' => $priceMapData['name'],
            ]);

        $this->assertDatabaseHas('price_maps', [
            'name' => $priceMapData['name'],
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdate(string $user): void
    {
        $this->{$user}->givePermissionTo('price-maps.edit');

        $response = $this
            ->actingAs($this->{$user})
            ->json('PATCH', '/price-maps/id:' . $this->priceMap->getKey(), [
                'name' => 'new price map name',
            ]);

        $response->assertOk()
            ->assertJsonFragment([
                'id' => $this->priceMap->getKey(),
                'name' => 'new price map name',
            ]);

        $this->assertDatabaseHas('price_maps', [
            'id' => $this->priceMap->getKey(),
            'name' => 'new price map name',
        ]);
    }
    public function testRemoveUnauthorized(): void
    {
        $this
            ->json('DELETE', '/price-maps/id:' . $this->priceMap->getKey())
            ->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testRemove(string $user): void
    {
        $this->{$user}->givePermissionTo('price-maps.remove');

        $response = $this
            ->actingAs($this->{$user})
            ->json('DELETE', '/price-maps/id:' . $this->priceMap->getKey());

        $response->assertNoContent();

        $this->assertDatabaseMissing('price_maps', [
            'id' => $this->priceMap->getKey(),
        ]);
    }
}
