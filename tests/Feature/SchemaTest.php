<?php

namespace Tests\Feature;

use App\Http\Controllers\SchemaController;
use App\Http\Requests\IndexSchemaRequest;
use App\Models\Item;
use App\Models\Option;
use App\Models\Schema;
use App\Services\Contracts\OptionServiceContract;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class SchemaTest extends TestCase
{
    use RefreshDatabase;

    public function testIndexUnauthorized(): void
    {
        Schema::factory()->count(5)->create();

        $response = $this->actingAs($this->user)->getJson('/schemas');

        $response->assertForbidden();
    }

    public function testIndexProductsAdd(): void
    {
        $this->user->givePermissionTo('products.add');

        Schema::factory()->count(5)->create();

        $response = $this->actingAs($this->user)->getJson('/schemas');

        $response
            ->assertOk()
            ->assertJsonCount(5, 'data');
    }

    /*public function testIndexWithPagination(): void
    {
        $optionServiceContractMock = $this->createMock(OptionServiceContract::class);

        $request = new IndexSchemaRequest();
        $request->merge(['limit' => 50, 'page' => 1]);

        $validator = Validator::make(['name' => 'test 1'], $request->rules());
        $this->assertTrue($validator->passes());

        $response = (new SchemaController($optionServiceContractMock))->index($request, static function ($request) { });
        $this->assertEquals($request->limit, $response->resource->toArray()['per_page']);
    }*/

    public function testIndexProductsEdit(): void
    {
        $this->user->givePermissionTo('products.edit');

        Schema::factory()->count(5)->create();

        $response = $this->actingAs($this->user)->getJson('/schemas');

        $response
            ->assertOk()
            ->assertJsonCount(5, 'data');
    }

    public function testShowUnauthorized(): void
    {
        $schema = Schema::factory()->create();

        $response = $this->actingAs($this->user)->getJson('/schemas/id:' . $schema->getKey());

        $response->assertForbidden();
    }

    public function testShowProductsAdd(): void
    {
        $this->user->givePermissionTo('products.add');

        $schema = Schema::factory()->create();

        $option1 = Option::factory()->create([
            'name' => 'A',
            'price' => 10,
            'disabled' => false,
            'order' => 0,
            'schema_id' => $schema->getKey(),
        ]);
        $option2 = Option::factory()->create([
            'name' => 'C',
            'price' => 100,
            'disabled' => false,
            'order' => 2,
            'schema_id' => $schema->getKey(),
        ]);
        $option3 = Option::factory()->create([
            'name' => 'B',
            'price' => 0,
            'disabled' => false,
            'order' => 1,
            'schema_id' => $schema->getKey(),
        ]);

        $response = $this->actingAs($this->user)->getJson('/schemas/id:' . $schema->getKey())
            ->assertOk()
            ->assertJsonFragment(['id' => $schema->getKey()]);

        $response = $response->json();

        $this->assertEquals($option1->getKey(), $response['data']['options'][0]['id']);
        $this->assertEquals($option3->getKey(), $response['data']['options'][1]['id']);
        $this->assertEquals($option2->getKey(), $response['data']['options'][2]['id']);
    }

    public function testShowProductsEdit(): void
    {
        $this->user->givePermissionTo('products.edit');

        $schema = Schema::factory()->create();

        $response = $this->actingAs($this->user)->getJson('/schemas/id:' . $schema->getKey());

        $response
            ->assertOk()
            ->assertJsonFragment(['id' => $schema->getKey()]);
    }

    public function testCreateUnauthorized(): void
    {
        $item = Item::factory()->create();

        $response = $this->actingAs($this->user)->postJson('/schemas', [
            'name' => 'Test',
            'type' => 'select',
            'price' => 120,
            'description' => 'test test',
            'hidden' => false,
            'required' => false,
            'options' => [
                [
                    'name' => 'L',
                    'price' => 0,
                    'disabled' => false,
                    'items' => [
                        $item->getKey(),
                    ],
                ],
            ],
        ]);

        $response->assertForbidden();
    }

    public function testCreateProductsAdd(): void
    {
        $this->user->givePermissionTo('products.add');

        $this->create();
    }

    public function testCreateProductsEdit(): void
    {
        $this->user->givePermissionTo('products.edit');

        $this->create();
    }

    public function create(): void
    {
        $item = Item::factory()->create();

        $response = $this->actingAs($this->user)->postJson('/schemas', [
            'name' => 'Test',
            'type' => 'select',
            'price' => 120,
            'description' => 'test test',
            'hidden' => false,
            'required' => false,
            'options' => [
                [
                    'name' => 'L',
                    'price' => 100,
                    'disabled' => false,
                    'items' => [
                        $item->getKey(),
                    ],
                ],
                [
                    'name' => 'A',
                    'price' => 1000,
                    'disabled' => false,
                ],
                [
                    'name' => 'B',
                    'price' => 0,
                    'disabled' => false,
                ],
            ],
        ]);

        $response->assertCreated();
        $schema = $response->getData()->data;
        $option = $response->getData()->data->options[0];

        $this->assertDatabaseHas('schemas', [
            'name' => 'Test',
            'type' => array_***REMOVED***(Schema::TYPES)['select'],
            'price' => 120,
            'description' => 'test test',
            'hidden' => 0,
            'required' => 0,
        ]);

        $this->assertDatabaseHas('options', [
            'id' => $option->id,
            'name' => 'L',
            'price' => 100,
            'disabled' => 0,
            'schema_id' => $schema->id,
            'order' => 0,
        ]);

        $this->assertDatabaseHas('options', [
            'name' => 'A',
            'price' => 1000,
            'disabled' => 0,
            'schema_id' => $schema->id,
            'order' => 1,
        ]);

        $this->assertDatabaseHas('options', [
            'name' => 'B',
            'price' => 0,
            'disabled' => 0,
            'schema_id' => $schema->id,
            'order' => 2,
        ]);

        $this->assertDatabaseHas('option_items', [
            'option_id' => $option->id,
            'item_id' => $item->getKey(),
        ]);
    }

    public function testCreateRelationUnauthorized(): void
    {
        $usedSchema = Schema::factory()->create();

        $response = $this->actingAs($this->user)->postJson('/schemas', [
            'name' => 'Multiplier',
            'type' => 'multiply_schema',
            'min' => 1,
            'max' => 10,
            'step' => 0.1,
            'used_schemas' => [
                $usedSchema->getKey(),
            ],
        ]);

        $response->assertForbidden();
    }

    public function testCreateRelationProductsAdd(): void
    {
        $this->user->givePermissionTo('products.add');

        $this->createRelation();
    }

    public function testCreateRelationProductsEdit(): void
    {
        $this->user->givePermissionTo('products.edit');

        $this->createRelation();
    }

    public function createRelation(): void
    {
        $usedSchema = Schema::factory()->create();

        $response = $this->actingAs($this->user)->postJson('/schemas', [
            'name' => 'Multiplier',
            'type' => 'multiply_schema',
            'min' => 1,
            'max' => 10,
            'step' => 0.1,
            'used_schemas' => [
                $usedSchema->getKey(),
            ],
        ]);

        $response->assertCreated();
        $schema = $response->getData()->data;

        $this->assertDatabaseHas('schema_used_schemas', [
            'schema_id' => $schema->id,
            'used_schema_id' => $usedSchema->getKey(),
        ]);
    }

    public function testUpdateUnauthorized(): void
    {
        $schema = Schema::factory()->create();

        $item = Item::factory()->create();

        $option = Option::factory()->create([
            'name' => 'L',
            'price' => 0,
            'disabled' => false,
            'schema_id' => $schema->getKey(),
        ]);

        $response = $this->actingAs($this->user)
            ->patchJson('/schemas/id:' . $schema->getKey() , [
                'name' => 'Test Updated',
                'price' => 200,
                'type' => 'select',
                'description' => 'test test',
                'hidden' => false,
                'required' => false,
                'options' => [
                    [
                        'id' => $option->getKey(),
                        'name' => 'L',
                        'price' => 0,
                        'disabled' => true,
                        'items' => [
                            $item->getKey(),
                        ],
                    ],
                ],
            ]);

        $response->assertForbidden();
    }

    public function testUpdateProductsAdd(): void
    {
        $this->user->givePermissionTo('products.add');

        $this->update();
    }

    public function testUpdateProductsEdit(): void
    {
        $this->user->givePermissionTo('products.edit');

        $this->update();
    }

    public function update(): void
    {
        $schema = Schema::factory()->create();

        $item = Item::factory()->create();
        $item2 = Item::factory()->create();

        $option = Option::factory()->create([
            'name' => 'L',
            'price' => 0,
            'disabled' => false,
            'schema_id' => $schema->getKey(),
        ]);
        $option->items()->sync([
            $item->getKey(),
            $item2->getKey(),
        ]);

        $option2 = Option::factory()->create([
            'name' => 'XL',
            'price' => 0,
            'disabled' => false,
            'schema_id' => $schema->getKey(),
        ]);

        $response = $this->actingAs($this->user)->patchJson('/schemas/id:' . $schema->getKey() , [
            'name' => 'Test Updated',
            'price' => 200,
            'type' => 'select',
            'description' => 'test test',
            'hidden' => false,
            'required' => false,
            'options' => [
                [
                    'id' => $option->getKey(),
                    'name' => 'L',
                    'price' => 0,
                    'disabled' => true,
                    'items' => [
                        $item->getKey(),
                    ],
                ],
            ],
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('schemas', [
            'name' => 'Test Updated',
            'price' => 200,
        ]);

        $this->assertDatabaseHas('options', [
            'id' => $option->getKey(),
            'name' => 'L',
            'price' => 0,
            'disabled' => 1,
            'schema_id' => $schema->getKey(),
        ]);

        $this->assertDatabaseMissing('options', [
            'id' => $option2->getKey(),
        ]);

        $this->assertDatabaseHas('option_items', [
            'option_id' => $option->getKey(),
            'item_id' => $item->getKey(),
        ]);

        $this->assertDatabaseMissing('option_items', [
            'option_id' => $option->getKey(),
            'item_id' => $item2->getKey(),
        ]);
    }

    public function testRemoveUnauthorized(): void
    {
        $schema = Schema::factory()->create();

        $response = $this->actingAs($this->user)
            ->deleteJson('/schemas/id:' . $schema->getKey());

        $response->assertForbidden();
    }

    public function testRemove(): void
    {
        $this->user->givePermissionTo('schemas.remove');

        $schema = Schema::factory()->create();

        $response = $this->actingAs($this->user)
            ->deleteJson('/schemas/id:' . $schema->getKey());

        $response->assertNoContent();
        $this->assertDeleted($schema);
    }

    public function testPrice(): void
    {
        $colors = Schema::create([
            'name' => 'Color',
            'price' => 0,
            'type' => 'select',
        ]);

        $red = $colors->options()->create([
            'name' => 'red',
            'price' => 10,
        ]);

        $green = $colors->options()->create([
            'name' => 'green',
            'price' => 20,
        ]);

        $blue = $colors->options()->create([
            'name' => 'blue',
            'price' => 30,
        ]);

        $this->assertEquals(10, $colors->getPrice($red->getKey(), [
            $colors->getKey() => $red->getKey(),
        ]));

        $this->assertEquals(20, $colors->getPrice($green->getKey(), [
            $colors->getKey() => $green->getKey(),
        ]));

        $this->assertEquals(30, $colors->getPrice($blue->getKey(), [
            $colors->getKey() => $blue->getKey(),
        ]));

        $multiplier = Schema::create([
            'name' => 'Price Multiplier',
            'type' => 'multiply',
            'price' => 10,
            'min' => 1,
            'max' => 10,
            'step' => 0.1,
        ]);

        $value = rand(10, 100) / 10;
        $this->assertEquals(10 * $value, $multiplier->getPrice($value, [
            $multiplier->getKey() => $value,
        ]));
    }

    public function testRelatedPrice(): void
    {
        $colors = Schema::create([
            'name' => 'Color',
            'price' => 0,
            'type' => 'select',
        ]);

        $red = $colors->options()->create([
            'name' => 'red',
            'price' => 10,
        ]);

        $multiplier = Schema::create([
            'name' => 'Multiplier',
            'type' => 'multiply_schema',
            'min' => 1,
            'max' => 10,
            'step' => 0.1,
        ]);

        $multiplier->usedSchemas()->attach($colors);

        $this->assertEquals(0, $colors->getPrice($red->getKey(), [
            $colors->getKey() => $red->getKey(),
            $multiplier->getKey() => 2,
        ]));

        $value = rand(10, 100) / 10;
        $this->assertEquals(10 * $value, $multiplier->getPrice($value, [
            $multiplier->getKey() => $value,
            $colors->getKey() => $red->getKey(),
        ]));
    }
}
