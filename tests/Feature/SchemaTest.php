<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\Schema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchemaTest extends TestCase
{
    use RefreshDatabase;

    public function testIndex(): void
    {
        Schema::factory()->count(5)->create();

        $response = $this->actingAs($this->user)->getJson('/schemas');

        $response
            ->assertOk()
            ->assertJsonCount(5, 'data');
    }

    public function testShow(): void
    {
        $schema = Schema::factory()->create();

        $response = $this->actingAs($this->user)->getJson('/schemas/id:' . $schema->getKey());

        $response
            ->assertOk()
            ->assertJsonFragment(['id' => $schema->getKey()]);
    }

    public function testCreate(): void
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
            'price' => 0,
            'disabled' => 0,
            'schema_id' => $schema->id,
        ]);

        $this->assertDatabaseHas('option_items', [
            'option_id' => $option->id,
            'item_id' => $item->getKey(),
        ]);
    }
}
