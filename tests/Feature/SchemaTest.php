<?php

namespace Tests\Feature;

use App\Enums\MetadataType;
use App\Enums\SchemaType;
use App\Models\Item;
use App\Models\Option;
use App\Models\Schema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class SchemaTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @dataProvider authProvider
     */
    public function testIndexUnauthorized(string $user): void
    {
        Schema::factory()->count(5)->create();

        $response = $this->actingAs($this->{$user})->getJson('/schemas');

        $response->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexProductsAdd(string $user): void
    {
        $this->{$user}->givePermissionTo('products.add');

        Schema::factory()->count(5)->create();

        $response = $this->actingAs($this->{$user})->getJson('/schemas');

        $response
            ->assertOk()
            ->assertJsonCount(5, 'data');
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexProductsWithTranslationsFlag(string $user): void
    {
        $this->{$user}->givePermissionTo('products.add');

        Schema::factory()->count(5)->create();

        $response = $this->actingAs($this->{$user})->getJson('/schemas?translations');

        $response
            ->assertOk()
            ->assertJsonCount(5, 'data');

        $firstElement = $response['data'][0];

        $this->assertArrayHasKey('translations', $firstElement);
        $this->assertIsArray($firstElement['translations']);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexWithPagination(string $user): void
    {
        $this->{$user}->givePermissionTo('products.add');

        Schema::factory()->count(20)->create();

        $this
            ->actingAs($this->{$user})
            ->json('GET', '/schemas', ['limit' => 10])
            ->assertOk()
            ->assertJsonCount(10, 'data');

        $this->assertEquals(Config::get('pagination.per_page'), 10);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexProductsEdit(string $user): void
    {
        $this->{$user}->givePermissionTo('products.edit');

        Schema::factory()->count(5)->create();

        $response = $this->actingAs($this->{$user})->getJson('/schemas');

        $response
            ->assertOk()
            ->assertJsonCount(5, 'data');
    }

    /**
     * @dataProvider booleanProvider
     */
    public function testIndexSearchByHidden(string $user, bool $boolean, bool $booleanValue): void
    {
        $this->{$user}->givePermissionTo('products.add');

        $hidden = Schema::factory()->create([
            'hidden' => true,
        ]);

        $visible = Schema::factory()->create([
            'hidden' => false,
        ]);

        $schemaId = $booleanValue ? $hidden->getKey() : $visible->getKey();

        $response = $this->actingAs($this->{$user})->json('GET', '/schemas', ['hidden' => $boolean]);

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment([
                'id' => $schemaId,
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexSearchByIds(string $user): void
    {
        $this->{$user}->givePermissionTo('products.add');

        $schema1 = Schema::factory()->create([
            'hidden' => false,
        ]);

        Schema::factory()->create([
            'hidden' => false,
        ]);

        $this->actingAs($this->{$user})->json('GET', '/schemas', ['ids' => [$schema1->getKey()]])
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    /**
     * @dataProvider booleanProvider
     */
    public function testIndexSearchByRequired(string $user, bool $boolean, bool $booleanValue): void
    {
        $this->{$user}->givePermissionTo('products.add');

        $hidden = Schema::factory()->create([
            'required' => true,
        ]);

        $visible = Schema::factory()->create([
            'required' => false,
        ]);

        $schemaId = $booleanValue ? $hidden->getKey() : $visible->getKey();

        $response = $this->actingAs($this->{$user})->json('GET', '/schemas', ['required' => $boolean]);

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment([
                'id' => $schemaId,
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testShowUnauthorized(string $user): void
    {
        $schema = Schema::factory()->create();

        $response = $this->actingAs($this->{$user})->getJson('/schemas/id:' . $schema->getKey());

        $response->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testShowProductsAdd(string $user): void
    {
        $this->{$user}->givePermissionTo('products.add');

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

        $response = $this->actingAs($this->{$user})->getJson('/schemas/id:' . $schema->getKey())
            ->assertOk()
            ->assertJsonFragment(['id' => $schema->getKey()]);

        $response = $response->json();

        $this->assertEquals($option1->getKey(), $response['data']['options'][0]['id']);
        $this->assertEquals($option3->getKey(), $response['data']['options'][1]['id']);
        $this->assertEquals($option2->getKey(), $response['data']['options'][2]['id']);
    }

    /**
     * @dataProvider authProvider
     */
    public function testShowProductsEdit(string $user): void
    {
        $this->{$user}->givePermissionTo('products.edit');

        $schema = Schema::factory()->create();

        $response = $this->actingAs($this->{$user})->getJson('/schemas/id:' . $schema->getKey());

        $response
            ->assertOk()
            ->assertJsonFragment(['id' => $schema->getKey()]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testShowWrongId(string $user): void
    {
        $this->{$user}->givePermissionTo('products.edit');

        $schema = Schema::factory()->create();

        $this
            ->actingAs($this->{$user})
            ->getJson('/schemas/id:its-not-uuid')
            ->assertNotFound();

        $this
            ->actingAs($this->{$user})
            ->getJson('/schemas/id:' . $schema->getKey() . $schema->getKey())
            ->assertNotFound();
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateUnauthorized(string $user): void
    {
        $item = Item::factory()->create();

        $response = $this->actingAs($this->{$user})->postJson('/schemas', [
            'name' => 'Test',
            'type' => SchemaType::getKey(SchemaType::SELECT),
            'price' => 120,
            'description' => 'test test',
            'hidden' => false,
            'required' => false,
            'default' => 0,
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

    /**
     * @dataProvider authProvider
     */
    public function testCreateProductsAdd(string $user): void
    {
        $this->{$user}->givePermissionTo('products.add');

        $this->create($user);
    }

    public function create(string $user): void
    {
        $item = Item::factory()->create();

        $response = $this->actingAs($this->{$user})->postJson('/schemas', [
            'name' => 'Test',
            'type' => SchemaType::getKey(SchemaType::SELECT),
            'price' => 120,
            'hidden' => false,
            'required' => false,
            'options' => [
                [
                    'price' => 100,
                    'disabled' => false,
                    'items' => [
                        $item->getKey(),
                    ],
                    'translations' => [$this->lang => [
                        'name' => 'L',
                    ]],
                ],
                [
                    'price' => 1000,
                    'disabled' => false,
                    'translations' => [$this->lang => [
                        'name' => 'A',
                    ]],
                ],
                [
                    'price' => 0,
                    'disabled' => false,
                    'translations' => [$this->lang => [
                        'name' => 'B',
                    ]],
                ],
            ],
            'translations' => [$this->lang => [
                'name' => 'Test',
                'description' => 'test test',
            ]],
            'published' => [$this->lang],
        ]);

        $response->assertCreated();
        $schema = $response->getData()->data;
        $option = $response->getData()->data->options[0];

        $this->assertDatabaseHas('schemas', [
            "name->{$this->lang}" => 'Test',
            'type' => SchemaType::SELECT,
            'price' => 120,
            "description->{$this->lang}" => 'test test',
            'hidden' => 0,
            'required' => 0,
            'default' => null,
            'available' => true,
        ]);

        $this->assertDatabaseHas('options', [
            'id' => $option->id,
            "name->{$this->lang}" => 'L',
            'price' => 100,
            'disabled' => 0,
            'schema_id' => $schema->id,
            'order' => 0,
            'available' => false,
        ]);

        $this->assertDatabaseHas('options', [
            "name->{$this->lang}" => 'A',
            'price' => 1000,
            'disabled' => 0,
            'schema_id' => $schema->id,
            'order' => 1,
            'available' => true,
        ]);

        $this->assertDatabaseHas('options', [
            "name->{$this->lang}" => 'B',
            'price' => 0,
            'disabled' => 0,
            'schema_id' => $schema->id,
            'order' => 2,
            'available' => true,
        ]);

        $this->assertDatabaseHas('option_items', [
            'option_id' => $option->id,
            'item_id' => $item->getKey(),
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateWithMetadata(string $user): void
    {
        $this->{$user}->givePermissionTo('products.add');

        $this
            ->actingAs($this->{$user})
            ->json('POST', '/schemas', [
                'translations' => [
                    $this->lang => [
                        'name' => 'Test',
                    ],
                ],
                'published' => [$this->lang],
                'type' => SchemaType::getKey(SchemaType::SELECT),
                'price' => 120,
                'hidden' => true,
                'required' => true,
                'metadata' => [
                    'attributeMeta' => 'attributeValue',
                ],
            ])
            ->assertCreated()
            ->assertJsonFragment([
                'metadata' => [
                    'attributeMeta' => 'attributeValue',
                ],
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateWithOptionMetadata(string $user): void
    {
        $this->{$user}->givePermissionTo('products.add');
        $this
            ->actingAs($this->{$user})
            ->json('POST', '/schemas', [
                'translations' => [
                    $this->lang => [
                        'name' => 'Test',
                    ],
                ],
                'published' => [$this->lang],
                'type' => SchemaType::getKey(SchemaType::SELECT),
                'price' => 120,
                'hidden' => false,
                'required' => false,
                'options' => [
                    [
                        'translations' => [
                            $this->lang => [
                                'name' => 'B',
                            ],
                        ],
                        'price' => 1000,
                        'disabled' => false,
                        'metadata' => [
                            'attributeMetaOption' => 'attributeValueOption',
                        ],
                    ],
                ],
                'metadata' => [
                    'attributeMeta' => 'attributeValue',
                ],
            ])
            ->assertCreated()
            ->assertJsonFragment([
                'name' => 'B',
                'price' => 1000,
                'disabled' => false,
                'metadata' => [
                    'attributeMetaOption' => 'attributeValueOption',
                ],
            ]);
    }

    /**
     * @dataProvider booleanProvider
     */
    public function testCreateWithMetadataPrivate(string $user, bool $boolean, bool $booleanValue): void
    {
        $this->{$user}->givePermissionTo(['products.add', 'schemas.show_metadata_private']);

        $response = $this->actingAs($this->{$user})->json('POST', '/schemas', [
            'translations' => [
                $this->lang => [
                    'name' => 'Test',
                ],
            ],
            'published' => [$this->lang],
            'type' => SchemaType::getKey(SchemaType::SELECT),
            'price' => 120,
            'hidden' => $boolean,
            'required' => $boolean,
            'metadata_private' => [
                'attributeMetaPriv' => 'attributeValue',
            ],
        ]);

        $response
            ->assertCreated()
            ->assertJsonFragment([
                'name' => 'Test',
                'price' => 120,
                'hidden' => $booleanValue,
                'required' => $booleanValue,
            ])
            ->assertJsonFragment([
                'metadata_private' => [
                    'attributeMetaPriv' => 'attributeValue',
                ],
            ]);
    }

    /**
     * @dataProvider booleanProvider
     */
    public function testCreateWithOptionMetadataPrivate(string $user, bool $boolean, bool $booleanValue): void
    {
        $this->{$user}->givePermissionTo(['products.add', 'options.show_metadata_private']);
        $this
            ->actingAs($this->{$user})
            ->json('POST', '/schemas', [
                'translations' => [
                    $this->lang => [
                        'name' => 'Test',
                    ],
                ],
                'published' => [$this->lang],
                'type' => SchemaType::getKey(SchemaType::SELECT),
                'price' => 120,
                'hidden' => $boolean,
                'required' => $boolean,
                'options' => [
                    [
                        'translations' => [
                            $this->lang => [
                                'name' => 'A',
                            ],
                        ],
                        'price' => 1000,
                        'disabled' => $boolean,
                        'metadata_private' => [
                            'attributeMetaPriv' => 'attributeValue',
                        ],
                    ],
                ],
            ])
            ->assertCreated()
            ->assertJsonFragment([
                'name' => 'Test',
                'price' => 120,
                'hidden' => $booleanValue,
                'required' => $booleanValue,
            ])
            ->assertJsonFragment([
                'name' => 'A',
                'price' => 1000,
                'disabled' => $booleanValue,
                'metadata_private' => [
                    'attributeMetaPriv' => 'attributeValue',
                ],
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateAvailableSchemaNonSelect(string $user): void
    {
        $this->{$user}->givePermissionTo('products.add');

        $response = $this->actingAs($this->{$user})->json('POST', '/schemas', [
            'translations' => [
                $this->lang => [
                    'name' => 'Test',
                ],
            ],
            'published' => [$this->lang],
            'type' => SchemaType::getKey(SchemaType::STRING),
            'price' => 120,
            'required' => false,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.available', true);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateAvailableSchemaAndOptionWithoutItem(string $user): void
    {
        $this->{$user}->givePermissionTo('products.add');

        $response = $this->actingAs($this->{$user})->json('POST', '/schemas', [
            'translations' => [
                $this->lang => [
                    'name' => 'Test',
                ],
            ],
            'published' => [$this->lang],
            'type' => SchemaType::getKey(SchemaType::SELECT),
            'price' => 120,
            'required' => false,
            'options' => [[
                'translations' => [
                    $this->lang => [
                        'name' => 'Test',
                    ],
                ],
                'price' => 0,
                'disabled' => false,
            ],
            ],
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.available', true)
            ->assertJsonPath('data.options.0.available', true);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateProductsEdit(string $user): void
    {
        $this->{$user}->givePermissionTo('products.edit');

        $this->create($user);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateRelationUnauthorized(string $user): void
    {
        $usedSchema = Schema::factory()->create();

        $this->actingAs($this->{$user})->postJson('/schemas', [
            'translations' => [
                $this->lang => [
                    'name' => 'Test',
                ],
            ],
            'published' => [$this->lang],
            'type' => SchemaType::getKey(SchemaType::MULTIPLY_SCHEMA),
            'min' => 1,
            'max' => 10,
            'step' => 0.1,
            'used_schemas' => [
                $usedSchema->getKey(),
            ],
        ])->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateRelationProductsAdd(string $user): void
    {
        $this->{$user}->givePermissionTo('products.add');

        $this->createRelation($user);
    }

    /**
     * @dataProvider authProvider
     */
    public function createRelation(string $user): void
    {
        $usedSchema = Schema::factory()->create();

        $response = $this->actingAs($this->{$user})->postJson('/schemas', [
            'translations' => [
                $this->lang => [
                    'name' => 'Multiplier',
                ],
            ],
            'published' => [$this->lang],
            'type' => SchemaType::getKey(SchemaType::MULTIPLY_SCHEMA),
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

    /**
     * @dataProvider authProvider
     */
    public function testCreateRelationProductsEdit(string $user): void
    {
        $this->{$user}->givePermissionTo('products.edit');

        $this->createRelation($user);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateUnauthorized(string $user): void
    {
        $schema = Schema::factory()->create();

        $item = Item::factory()->create();

        $option = Option::factory()->create([
            'name' => 'L',
            'price' => 0,
            'disabled' => false,
            'schema_id' => $schema->getKey(),
        ]);

        $response = $this->actingAs($this->{$user})
            ->patchJson('/schemas/id:' . $schema->getKey(), [
                'translations' => [
                    $this->lang => [
                        'name' => 'Test',
                    ],
                ],
                'published' => [$this->lang],
                'price' => 200,
                'type' => SchemaType::getKey(SchemaType::SELECT),
                'hidden' => false,
                'required' => false,
                'options' => [
                    [
                        'id' => $option->getKey(),
                        'translations' => [
                            $this->lang => [
                                'name' => 'Test',
                            ],
                        ],
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

    /**
     * @dataProvider authProvider
     */
    public function testUpdateProductsAdd(string $user): void
    {
        $this->{$user}->givePermissionTo('products.add');

        $this->update($user);
    }

    /**
     * @dataProvider authProvider
     */
    public function update(string $user): void
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

        $response = $this->actingAs($this->{$user})->patchJson('/schemas/id:' . $schema->getKey(), [
            'translations' => [
                $this->lang => [
                    'name' => 'Test Updated',
                    'description' => 'test test',
                ],
            ],
            'published' => [$this->lang],
            'price' => 200,
            'type' => SchemaType::getKey(SchemaType::SELECT),
            'hidden' => false,
            'required' => false,
            'default' => 0,
            'options' => [
                [
                    'id' => $option->getKey(),
                    'price' => 0,
                    'disabled' => true,
                    'items' => [
                        $item->getKey(),
                    ],
                    'translations' => [$this->lang => [
                        'name' => 'L',
                    ]],
                ],
            ],
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('schemas', [
            "name->{$this->lang}" => 'Test Updated',
            'price' => 200,
            'default' => 0,
        ]);

        $this->assertDatabaseHas('options', [
            'id' => $option->getKey(),
            "name->{$this->lang}" => 'L',
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

    /**
     * @dataProvider authProvider
     *
     * TODO: WTF??
     */
    public function testUpdateWithEmptyData(string $user): void
    {
        $this->{$user}->givePermissionTo('products.edit');

        $schema = Schema::factory()->create([
            'name' => 'new schema',
            'description' => 'new schema description',
            'price' => 10,
            'hidden' => false,
            'required' => true,
            'max' => 10,
            'min' => 1,
        ]);

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

        $response = $this->actingAs($this->{$user})->patchJson('/schemas/id:' . $schema->getKey(), []);

        $response->assertOk();

        $this->assertDatabaseHas('schemas', [
            "name->{$this->lang}" => 'new schema',
            "description->{$this->lang}" => 'new schema description',
            'price' => 10,
            'hidden' => false,
            'required' => true,
            'max' => 10,
            'min' => 1,
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateProductsEdit(string $user): void
    {
        $this->{$user}->givePermissionTo('products.edit');

        $this->update($user);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateWithMetadata(string $user): void
    {
        $this->{$user}->givePermissionTo('products.edit');

        $schema = Schema::factory()->create();

        $schema->metadata()->create([
            'name' => 'first',
            'value' => 'metadata',
            'value_type' => MetadataType::STRING,
            'public' => true,
        ]);

        $this
            ->actingAs($this->{$user})
            ->json('PATCH', '/schemas/id:' . $schema->getKey(), [
                'metadata' => [
                    'first' => 'new value',
                    'second' => 'new metadata',
                ],
            ])
            ->assertOk()
            ->assertJsonFragment([
                'metadata' => [
                    'first' => 'metadata',
                ],
            ])
            ->assertJsonMissing([
                'first' => 'new value',
            ])
            ->assertJsonMissing([
                'second' => 'new metadata',
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testRemoveUnauthorized(string $user): void
    {
        $schema = Schema::factory()->create();

        $response = $this->actingAs($this->{$user})
            ->deleteJson('/schemas/id:' . $schema->getKey());

        $response->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testRemove(string $user): void
    {
        $this->{$user}->givePermissionTo('schemas.remove');

        $schema = Schema::factory()->create();

        $response = $this->actingAs($this->{$user})
            ->deleteJson('/schemas/id:' . $schema->getKey());

        $response->assertNoContent();
        $this->assertModelMissing($schema);
    }

    public function testPrice(): void
    {
        $colors = Schema::create([
            'name' => 'Color',
            'price' => 0,
            'type' => SchemaType::SELECT,
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
            'type' => SchemaType::MULTIPLY,
            'price' => 10,
            'min' => 1,
            'max' => 10,
            'step' => 0.1,
        ]);

        $value = mt_rand(10, 100) / 10;
        $this->assertEquals(10 * $value, $multiplier->getPrice($value, [
            $multiplier->getKey() => $value,
        ]));
    }

    public function testRelatedPrice(): void
    {
        $colors = Schema::create([
            'name' => 'Color',
            'price' => 0,
            'type' => SchemaType::SELECT,
        ]);

        $red = $colors->options()->create([
            'name' => 'red',
            'price' => 10,
        ]);

        $multiplier = Schema::create([
            'name' => 'Multiplier',
            'type' => SchemaType::MULTIPLY_SCHEMA,
            'min' => 1,
            'max' => 10,
            'step' => 0.1,
        ]);

        $multiplier->usedSchemas()->attach($colors);

        $this->assertEquals(0, $colors->getPrice($red->getKey(), [
            $colors->getKey() => $red->getKey(),
            $multiplier->getKey() => 2,
        ]));

        $value = mt_rand(10, 100) / 10;
        $this->assertEquals(10 * $value, $multiplier->getPrice($value, [
            $multiplier->getKey() => $value,
            $colors->getKey() => $red->getKey(),
        ]));
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateWithOptionPriceAndDisabledNull(string $user): void
    {
        $this->{$user}->givePermissionTo('products.edit');
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

        $response = $this->actingAs($this->{$user})->json('PATCH', '/schemas/id:' . $schema->getKey(), [
            'name' => 'Test Updated',
            'price' => 200,
            'type' => SchemaType::getKey(SchemaType::SELECT),
            'description' => 'test test',
            'hidden' => false,
            'required' => false,
            'default' => 0,
            'options' => [
                [
                    'id' => $option->getKey(),
                    'name' => 'L',
                    'price' => null,
                    'disabled' => null,
                    'items' => [
                        $item->getKey(),
                    ],
                ],
            ],
        ]);

        $response->assertStatus(422);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateWithOptionPriceAndDisabledNull(string $user): void
    {
        $this->{$user}->givePermissionTo('products.add');
        $item = Item::factory()->create();

        $response = $this->actingAs($this->{$user})->postJson('/schemas', [
            'name' => 'Test',
            'type' => SchemaType::getKey(SchemaType::SELECT),
            'price' => 120,
            'description' => 'test test',
            'hidden' => false,
            'required' => false,
            'options' => [
                [
                    'name' => 'L',
                    'price' => null,
                    'disabled' => null,
                    'items' => [
                        $item->getKey(),
                    ],
                ],
            ],
        ]);

        $response->assertStatus(422);
    }
}
