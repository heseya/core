<?php

namespace Tests\Feature\Manufacturers;

use App\Models\Product;
use Domain\Manufacturer\Models\Manufacturer;
use Tests\TestCase;

class ManufacturerTest extends TestCase
{
    public Manufacturer $manufacturer;

    public function setUp(): void
    {
        parent::setUp();

        $this->manufacturer = Manufacturer::factory()->create([
            'name' => 'Searched',
        ]);
    }

    public function testIndexUnauthorized(): void
    {
        $this->json('GET', '/manufacturers')
            ->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndex(string $user): void
    {
        $this->{$user}->givePermissionTo('manufacturers.show');

        Manufacturer::factory()->count(5)->create();

        $this
            ->actingAs($this->{$user})
            ->json('GET', '/manufacturers')
            ->assertOk()
            ->assertJsonCount(6, 'data');
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexSearch(string $user): void
    {
        $this->{$user}->givePermissionTo('manufacturers.show');

        Manufacturer::factory()->count(5)->create();

        $this
            ->actingAs($this->{$user})
            ->json('GET', '/manufacturers', ['search' => 'Searched'])
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment([
                'id' => $this->manufacturer->getKey(),
            ]);
    }

    public function testShowUnauthorized(): void
    {
        $this->json('GET', '/manufacturers/id:' . $this->manufacturer->getKey())
            ->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testShow(string $user): void
    {
        $this->{$user}->givePermissionTo('manufacturers.show_details');

        $this
            ->actingAs($this->{$user})
            ->json('GET', '/manufacturers/id:' . $this->manufacturer->getKey())
            ->assertOk()
            ->assertJsonFragment([
                'id' => $this->manufacturer->getKey(),
                'name' => $this->manufacturer->name,
            ]);
    }

    public function testCreateUnauthorized(): void
    {
        $this
            ->json('POST', '/manufacturers', [
                'name' => 'Test',
                'email' => 'test@example.com',
                'address' => [
                    'name' => 'Test',
                    'address' => 'Test 12',
                    'phone' => '123456789',
                    'zip' => '12-123',
                    'city' => 'City',
                    'country' => 'PL',
                ]
            ])
            ->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreate(string $user): void
    {
        $this->{$user}->givePermissionTo('manufacturers.add');

        $product = Product::factory()->create([
            'public' => true,
        ]);

        $this
            ->actingAs($this->{$user})
            ->json('POST', '/manufacturers', [
                'name' => 'Test',
                'email' => 'test@example.com',
                'address' => [
                    'name' => 'Jan Kowalski',
                    'address' => 'Test 12',
                    'zip' => '12-123',
                    'city' => 'City',
                    'country' => 'PL',
                ],
                'product_ids' => [
                    $product->getKey(),
                ],
            ])
            ->assertCreated()
            ->assertJsonFragment([
                $product->getKey(),
            ]);
    }

    public function testUpdateUnauthorized(): void
    {
        $this
            ->json('PATCH', '/manufacturers/id:' . $this->manufacturer->getKey(), [
                'name' => 'Test',
                'email' => 'test@example.com',
                'address' => [
                    'name' => 'Test',
                    'address' => 'Test 12',
                    'phone' => '123456789',
                    'zip' => '12-123',
                    'city' => 'City',
                    'country' => 'PL',
                ]
            ])
            ->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdate(string $user): void
    {
        $this->{$user}->givePermissionTo('manufacturers.edit');

        $product = Product::factory()->create([
            'public' => true,
            'manufacturer_id' => $this->manufacturer->getKey(),
        ]);

        $oldProduct = Product::factory()->create([
            'public' => true,
            'manufacturer_id' => $this->manufacturer->getKey(),
        ]);

        $newProduct = Product::factory()->create([
            'public' => true,
        ]);

        $this
            ->actingAs($this->{$user})
            ->json('PATCH', '/manufacturers/id:' . $this->manufacturer->getKey(), [
                'name' => 'Test',
                'email' => 'test@example.com',
                'address' => [
                    'name' => 'Jan Kowalski',
                    'address' => 'Test 12',
                    'zip' => '12-123',
                    'city' => 'City',
                    'country' => 'PL',
                ],
                'product_ids' => [
                    $product->getKey(),
                    $newProduct->getKey(),
                ],
            ])
            ->assertOk()
            ->assertJsonFragment([
                $product->getKey(),
            ])
            ->assertJsonFragment([
                $newProduct->getKey(),
            ])
            ->assertJsonMissing([
                $oldProduct->getKey(),
            ]);
    }

    public function testDeleteUnauthorized(): void
    {
        $this->json('DELETE', '/manufacturers/id:' . $this->manufacturer->getKey())
            ->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testDelete(string $user): void
    {
        $this->{$user}->givePermissionTo('manufacturers.remove');

        $this
            ->actingAs($this->{$user})
            ->json('DELETE', '/manufacturers/id:' . $this->manufacturer->getKey())
            ->assertNoContent();

        $this->assertDatabaseMissing('manufacturers', [
            'id' => $this->manufacturer->getKey(),
        ]);
    }
}
