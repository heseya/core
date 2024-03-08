<?php

namespace Tests\Feature\Items;

use App\Models\Deposit;
use App\Models\Item;
use App\Models\Product;
use App\Services\SchemaCrudService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use Tests\Utils\FakeDto;

class ItemViewTest extends ItemTestCase
{
    private SchemaCrudService $schemaCrudService;

    public function setUp(): void
    {
        parent::setUp();

        $this->schemaCrudService = App::make(SchemaCrudService::class);
    }

    public function testViewUnauthorized(): void
    {
        $response = $this->getJson('/items/id:' . $this->item->getKey());
        $response->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testView(string $user): void
    {
        $this->{$user}->givePermissionTo('items.show_details');

        $this
            ->actingAs($this->{$user})
            ->getJson('/items/id:' . $this->item->getKey())
            ->assertOk()
            ->assertJson(['data' => $this->expected + ['products' => [], 'schemas' => []]]);

        $this->assertQueryCountLessThan(11);
    }

    /**
     * @dataProvider authProvider
     */
    public function testViewWithProducts(string $user): void
    {
        $this->{$user}->givePermissionTo('items.show_details');

        $product1 = Product::factory()->create(['public' => true]);
        $product1->items()->attach([$this->item->getKey() => [
            'required_quantity' => 1,
        ]]);

        $product2 = Product::factory()->create(['public' => true]);
        $product2->items()->attach([$this->item->getKey() => [
            'required_quantity' => 1,
        ]]);

        $this
            ->actingAs($this->{$user})
            ->getJson('/items/id:' . $this->item->getKey())
            ->assertOk()
            ->assertJson(['data' => $this->expected])
            ->assertJsonCount(2, 'data.products')
            ->assertJsonFragment([
                'id' => $product1->getKey(),
                'name' => $product1->name,
            ])
            ->assertJsonFragment([
                'id' => $product2->getKey(),
                'name' => $product2->name,
            ]);

        $this->assertQueryCountLessThan(18);
    }

    /**
     * @dataProvider authProvider
     */
    public function testViewWithSchemas(string $user): void
    {
        $this->{$user}->givePermissionTo('items.show_details');

        $schema1 = $this->schemaCrudService->store(FakeDto::schemaDto([
            'type' => 'select',
            'prices' => [['value' => 0, 'currency' => $this->currency->value]],
            'hidden' => false,
            'required' => true,
        ]));

        $option1 = $schema1->options()->create([
            'name' => 'XL',
            'prices' => [['value' => 0, 'currency' => $this->currency->value]],
        ]);
        $option1->items()->sync([$this->item->getKey()]);

        $schema2 = $this->schemaCrudService->store(FakeDto::schemaDto([
            'type' => 'select',
            'prices' => [['value' => 0, 'currency' => $this->currency->value]],
            'hidden' => false,
            'required' => false,
        ]));

        $option2 = $schema2->options()->create([
            'name' => 'XL',
            'prices' => [['value' => 0, 'currency' => $this->currency->value]],
        ]);
        $option2->items()->sync([$this->item->getKey()]);

        $option3 = $schema2->options()->create([
            'name' => 'XL',
            'prices' => [['value' => 0, 'currency' => $this->currency->value]],
        ]);
        $option3->items()->sync([$this->item->getKey()]);

        $this
            ->actingAs($this->{$user})
            ->getJson('/items/id:' . $this->item->getKey())
            ->assertOk()
            ->assertJson(['data' => $this->expected])
            ->assertJsonCount(2, 'data.schemas')
            ->assertJsonFragment([
                'id' => $schema1->getKey(),
                'name' => $schema1->name,
            ])
            ->assertJsonFragment([
                'id' => $schema2->getKey(),
                'name' => $schema2->name,
            ]);

        $this->assertQueryCountLessThan(12);
    }

    /**
     * @dataProvider authProvider
     */
    public function testViewWrongId(string $user): void
    {
        $this->{$user}->givePermissionTo('items.show_details');

        $this
            ->actingAs($this->{$user})
            ->getJson('/items/id:its-not-id')
            ->assertNotFound();

        $this
            ->actingAs($this->{$user})
            ->getJson('/items/id:' . $this->item->getKey() . $this->item->getKey())
            ->assertNotFound();
    }

    /**
     * @dataProvider authProvider
     */
    public function testShowWhitAvailability(string $user): void
    {
        $this->{$user}->givePermissionTo('items.show_details');

        $item = Item::factory()->create();

        $time = 4;
        Deposit::factory()->create([
            'item_id' => $item->getKey(),
            'quantity' => 2.0,
            'shipping_time' => $time,
        ]);
        Deposit::factory()->create([
            'item_id' => $item->getKey(),
            'quantity' => 2.0,
            'shipping_time' => $time,
        ]);
        Deposit::factory()->create([
            'item_id' => $item->getKey(),
            'quantity' => 2.0,
            'shipping_time' => $time + 5,
        ]);
        $date = Carbon::now()->startOfDay()->addDays(5)->toIso8601String();
        Deposit::factory()->create([
            'item_id' => $item->getKey(),
            'quantity' => 2.0,
            'shipping_date' => $date,
            'shipping_time' => null,
        ]);
        Deposit::factory()->create([
            'item_id' => $item->getKey(),
            'quantity' => 2.0,
            'shipping_time' => null,
        ]);
        Deposit::factory()->create([
            'item_id' => $item->getKey(),
            'quantity' => 2.0,
            'shipping_time' => $time + 1,
        ]);
        Deposit::factory()->create([
            'item_id' => $item->getKey(),
            'quantity' => -2.0,
            'shipping_time' => $time + 1,
        ]);

        $this
            ->actingAs($this->{$user})
            ->getJson('/items/id:' . $item->getKey())
            ->assertOk()
            ->assertJsonFragment([
                'availability' => [
                    ['quantity' => 2, 'shipping_time' => null, 'shipping_date' => null, 'from_unlimited' => false],
                    ['quantity' => 2, 'shipping_time' => null, 'shipping_date' => $date, 'from_unlimited' => false],
                    ['quantity' => 4, 'shipping_time' => 4, 'shipping_date' => null, 'from_unlimited' => false],
                    ['quantity' => 2, 'shipping_time' => 9, 'shipping_date' => null, 'from_unlimited' => false],
                ],
            ]);
    }
}
