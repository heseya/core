<?php

namespace Tests\Feature;

use App\Enums\SchemaType;
use App\Models\Item;
use App\Models\Option;
use App\Models\Price;
use Brick\Money\Money;
use Domain\Currency\Currency;
use Domain\Language\Language;
use Domain\Metadata\Enums\MetadataType;
use Domain\Price\Dtos\PriceDto;
use Domain\ProductSchema\Models\Schema;
use Domain\ProductSchema\Services\SchemaCrudService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;
use Tests\Utils\FakeDto;

class SchemaTest extends TestCase
{
    use RefreshDatabase;

    private SchemaCrudService $schemaCrudService;
    private Currency $currency;

    public function setUp(): void
    {
        parent::setUp();

        $this->schemaCrudService = App::make(SchemaCrudService::class);

        $this->currency = Currency::DEFAULT;
    }

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

        $response->assertOk()
            ->assertJsonCount(5, 'data');
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexProductsWithTranslationsFlag(string $user): void
    {
        $this->{$user}->givePermissionTo('products.add');

        Schema::factory()->count(5)->create();

        $response = $this->actingAs($this->{$user})->getJson('/schemas?with_translations=1');

        $response->assertOk()
            ->assertJsonCount(5, 'data');

        $firstElement = $response['data'][0];

        $this->assertArrayHasKey('translations', $firstElement);
        $this->assertIsArray($firstElement['translations']);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexProductsWithTranslationsFlagMultiLang(string $user): void
    {
        $this->{$user}->givePermissionTo(['products.add', 'schemas.show_hidden']);

        /** @var Schema $schema */
        $schema = Schema::factory()->create([
            'name' => 'Nazwa',
            'description' => 'Opis',
        ]);

        /** @var Language $en */
        $en = Language::query()->where('iso', '=', 'en')->first();

        $schema->setLocale($en->getKey())->fill([
            'name' => 'Name',
            'description' => 'Description',
        ]);
        $schema->save();

        $response = $this->actingAs($this->{$user})->json('GET', '/schemas?with_translations=1');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment([
                'translations' => [
                    $this->lang => [
                        'name' => 'Nazwa',
                        'description' => 'Opis',
                    ],
                    $en->getKey() => [
                        'name' => 'Name',
                        'description' => 'Description',
                    ],
                ],
            ]);

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

        $response->assertOk()
            ->assertJsonCount(5, 'data');
    }

    /**
     * @dataProvider authWithTwoBooleansProvider
     */
    public function testIndexSearchByHidden(string $user, bool $boolean, bool $booleanValue): void
    {
        $this->{$user}->givePermissionTo('products.add');

        $hidden = $this->schemaCrudService->store(FakeDto::schemaDto([
            'hidden' => true,
        ]));

        $visible = $this->schemaCrudService->store(FakeDto::schemaDto([
            'hidden' => false,
        ]));

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

        $schema1 = $this->schemaCrudService->store(FakeDto::schemaDto([
            'hidden' => false,
        ]));

        $this->schemaCrudService->store(FakeDto::schemaDto([
            'hidden' => false,
        ]));

        $this->actingAs($this->{$user})->json('GET', '/schemas', ['ids' => [$schema1->getKey()]])
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    /**
     * @dataProvider authWithTwoBooleansProvider
     */
    public function testIndexSearchByRequired(string $user, bool $boolean, bool $booleanValue): void
    {
        $this->{$user}->givePermissionTo('products.add');

        $hidden = $this->schemaCrudService->store(FakeDto::schemaDto([
            'required' => true,
        ]));

        $visible = $this->schemaCrudService->store(FakeDto::schemaDto([
            'required' => false,
        ]));

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
        $schema = $this->schemaCrudService->store(FakeDto::schemaDto());

        $response = $this->actingAs($this->{$user})->getJson('/schemas/id:' . $schema->getKey());

        $response->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testShowProductsAdd(string $user): void
    {
        $this->{$user}->givePermissionTo('products.add');

        $schema = $this->schemaCrudService->store(FakeDto::schemaDto([], false, false));

        $option1 = Option::factory()->create([
            'name' => 'A',
            'prices' => [['value' => 10, 'currency' => $this->currency->value]],
            'order' => 0,
            'schema_id' => $schema->getKey(),
        ]);
        $option2 = Option::factory()->create([
            'name' => 'C',
            'prices' => [['value' => 100, 'currency' => $this->currency->value]],
            'order' => 2,
            'schema_id' => $schema->getKey(),
        ]);
        $option3 = Option::factory()->create([
            'name' => 'B',
            'prices' => [['value' => 0, 'currency' => $this->currency->value]],
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

        $schema = $this->schemaCrudService->store(FakeDto::schemaDto());

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

        $schema = $this->schemaCrudService->store(FakeDto::schemaDto());

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

        $response = $this->actingAs($this->{$user})->postJson('/schemas', FakeDto::schemaData([
            'name' => 'Test',
            'description' => 'test test',
            'hidden' => false,
            'required' => false,
            'default' => 0,
            'options' => [
                [
                    'name' => 'L',
                    'prices' => [['value' => 0, 'currency' => $this->currency->value]],
                    'items' => [
                        $item->getKey(),
                    ],
                ],
            ],
        ]));

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

        $data = FakeDto::schemaData([
            'name' => 'Test',
            'hidden' => false,
            'required' => false,
            'options' => [
                [
                    'prices' => [
                        ['value' => 100, 'currency' => Currency::DEFAULT->value,]
                    ],
                    'items' => [
                        $item->getKey(),
                    ],
                    'translations' => [$this->lang => [
                        'name' => 'L',
                    ]],
                ],
                [
                    'prices' => [
                        ['value' => 1000, 'currency' => Currency::DEFAULT->value,]
                    ],
                    'translations' => [$this->lang => [
                        'name' => 'A',
                    ]],
                ],
                [
                    'prices' => [
                        ['value' => 0, 'currency' => Currency::DEFAULT->value,]
                    ],
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

        $response = $this->actingAs($this->{$user})->postJson('/schemas', $data);

        $response->assertValid()
            ->assertCreated();

        $schema = $response->getData()->data;
        $option = $response->getData()->data->options[0];
        $option1 = $response->getData()->data->options[1];
        $option2 = $response->getData()->data->options[2];

        $this->assertDatabaseHas('schemas', [
            "name->{$this->lang}" => 'Test',
            'type' => SchemaType::SELECT,
            "description->{$this->lang}" => 'test test',
            'hidden' => 0,
            'required' => 0,
            'default' => null,
            'available' => true,
        ]);

        $this->assertDatabaseHas('options', [
            'id' => $option->id,
            "name->{$this->lang}" => 'L',
            'schema_id' => $schema->id,
            'order' => 0,
            'available' => false,
        ]);

        $this->assertDatabaseHas('prices', [
            'value' => "10000",
            'model_id' => $option->id,
            'model_type' => 'Option',
        ]);

        $this->assertDatabaseHas('options', [
            "name->{$this->lang}" => 'A',
            'schema_id' => $schema->id,
            'order' => 1,
            'available' => true,
        ]);

        $this->assertDatabaseHas('prices', [
            'value' => "100000",
            'model_id' => $option1->id,
            'model_type' => 'Option',
        ]);

        $this->assertDatabaseHas('options', [
            "name->{$this->lang}" => 'B',
            'schema_id' => $schema->id,
            'order' => 2,
            'available' => true,
        ]);

        $this->assertDatabaseHas('prices', [
            'value' => "0",
            'model_id' => $option2->id,
            'model_type' => 'Option',
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
            ->json('POST', '/schemas', FakeDto::schemaData([
                'translations' => [
                    $this->lang => [
                        'name' => 'Test',
                    ],
                ],
                'published' => [$this->lang],
                'hidden' => true,
                'required' => true,
                'metadata' => [
                    'attributeMeta' => 'attributeValue',
                ],
            ]))
            ->assertValid()
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
            ->json('POST', '/schemas', FakeDto::schemaData([
                'translations' => [
                    $this->lang => [
                        'name' => 'Test',
                    ],
                ],
                'published' => [$this->lang],
                'hidden' => false,
                'required' => false,
                'options' => [
                    [
                        'translations' => [
                            $this->lang => [
                                'name' => 'B',
                            ],
                        ],
                        'prices' => [['value' => 1000, 'currency' => $this->currency->value]],
                        'metadata' => [
                            'attributeMetaOption' => 'attributeValueOption',
                        ],
                    ],
                ],
                'metadata' => [
                    'attributeMeta' => 'attributeValue',
                ],
            ]))
            ->assertValid()
            ->assertCreated()
            ->assertJsonFragment([
                'name' => 'B',
                'metadata' => [
                    'attributeMetaOption' => 'attributeValueOption',
                ],
            ]);
    }

    /**
     * @dataProvider authWithTwoBooleansProvider
     */
    public function testCreateWithMetadataPrivate(string $user, bool $boolean, bool $booleanValue): void
    {
        $this->{$user}->givePermissionTo(['products.add', 'schemas.show_metadata_private']);

        $response = $this->actingAs($this->{$user})->json('POST', '/schemas', FakeDto::schemaData([
            'translations' => [
                $this->lang => [
                    'name' => 'Test',
                ],
            ],
            'published' => [$this->lang],
            'hidden' => $boolean,
            'required' => $boolean,
            'metadata_private' => [
                'attributeMetaPriv' => 'attributeValue',
            ],
        ]));

        $response
            ->assertValid()
            ->assertCreated()
            ->assertJsonFragment([
                'name' => 'Test',
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
     * @dataProvider authWithTwoBooleansProvider
     */
    public function testCreateWithOptionMetadataPrivate(string $user, bool $boolean, bool $booleanValue): void
    {
        $this->{$user}->givePermissionTo(['products.add', 'options.show_metadata_private']);
        $this
            ->actingAs($this->{$user})
            ->json('POST', '/schemas', FakeDto::schemaData([
                'translations' => [
                    $this->lang => [
                        'name' => 'Test',
                    ],
                ],
                'published' => [$this->lang],
                'hidden' => $boolean,
                'required' => $boolean,
                'options' => [
                    [
                        'translations' => [
                            $this->lang => [
                                'name' => 'A',
                            ],
                        ],
                        'prices' => [['value' => 1000, 'currency' => $this->currency->value]],
                        'metadata_private' => [
                            'attributeMetaPriv' => 'attributeValue',
                        ],
                    ],
                ],
            ]))
            ->assertValid()
            ->assertCreated()
            ->assertJsonFragment([
                'name' => 'Test',
                'hidden' => $booleanValue,
                'required' => $booleanValue,
            ])
            ->assertJsonFragment([
                'name' => 'A',
                'metadata_private' => [
                    'attributeMetaPriv' => 'attributeValue',
                ],
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateAvailableSchemaAndOptionWithoutItem(string $user): void
    {
        $this->{$user}->givePermissionTo('products.add');

        $response = $this->actingAs($this->{$user})->json('POST', '/schemas', FakeDto::schemaData([
            'translations' => [
                $this->lang => [
                    'name' => 'Test',
                ],
            ],
            'published' => [$this->lang],
            'required' => false,
            'options' => [
                [
                    'translations' => [
                        $this->lang => [
                            'name' => 'Test',
                        ],
                    ],
                    'prices' => [['value' => 0, 'currency' => $this->currency->value]],
                ],
            ],
        ]));

        $response
            ->assertValid()
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
        $usedSchema = $this->schemaCrudService->store(FakeDto::schemaDto());

        $this->actingAs($this->{$user})->postJson('/schemas', [
            'translations' => [
                $this->lang => [
                    'name' => 'Test',
                ],
            ],
            'published' => [$this->lang],
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
        $usedSchema = $this->schemaCrudService->store(FakeDto::schemaDto());

        $response = $this->actingAs($this->{$user})->postJson('/schemas', FakeDto::schemaData([
            'translations' => [
                $this->lang => [
                    'name' => 'Multiplier',
                ],
            ],
            'published' => [$this->lang],
            'used_schemas' => [
                $usedSchema->getKey(),
            ],
        ]));

        $response->assertValid()->assertCreated();
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
        $schema = $this->schemaCrudService->store(FakeDto::schemaDto());

        $item = Item::factory()->create();

        $option = Option::factory()->create([
            'name' => 'L',
            'schema_id' => $schema->getKey(),
        ]);
        $option->prices()->createMany(Price::factory(['value' => 0])->prepareForCreateMany());

        $response = $this->actingAs($this->{$user})
            ->patchJson('/schemas/id:' . $schema->getKey(), FakeDto::schemaData([
                'translations' => [
                    $this->lang => [
                        'name' => 'Test',
                    ],
                ],
                'published' => [$this->lang],
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
                        'prices' => [['value' => 0, 'currency' => $this->currency->value]],
                        'items' => [
                            $item->getKey(),
                        ],
                    ],
                ],
            ]));

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
        $schema = $this->schemaCrudService->store(FakeDto::schemaDto([
            'options' => [
                [
                    'name' => 'L',
                    'prices' => [PriceDto::from(Money::of(0, Currency::DEFAULT->value))],
                ] + Option::factory()->definition(),
                [
                    'name' => 'XL',
                    'prices' => [PriceDto::from(Money::of(0, Currency::DEFAULT->value))],
                ] + Option::factory()->definition(),
            ]
        ]));

        $item = Item::factory()->create();
        $item2 = Item::factory()->create();

        $option = $schema->options->where('name', 'L')->first();
        $option->items()->sync([
            $item->getKey(),
            $item2->getKey(),
        ]);

        $option2 = $schema->options->where('name', 'XL')->first();

        $data = FakeDto::schemaData([
            'translations' => [
                $this->lang => [
                    'name' => 'Test Updated',
                    'description' => 'test test',
                ],
            ],
            'published' => [$this->lang],
            'hidden' => false,
            'required' => false,
            'default' => null,
            'options' => [
                [
                    'id' => $option->getKey(),
                    'prices' => [PriceDto::from(Money::of(0, Currency::DEFAULT->value))],
                    'items' => [
                        $item->getKey(),
                    ],
                    'translations' => [
                        $this->lang => [
                            'name' => 'L',
                        ]
                    ],
                ],
            ],
        ]);

        $response = $this->actingAs($this->{$user})->patchJson('/schemas/id:' . $schema->getKey(), $data);

        $response->assertValid()->assertOk();

        $this->assertDatabaseHas('schemas', [
            "name->{$this->lang}" => 'Test Updated',
            'default' => null,
        ]);

        $this->assertDatabaseHas('prices', [
            'value' => "20000",
            'currency' => Currency::DEFAULT->value,
            'model_id' => $schema->getKey(),
        ]);

        $this->assertDatabaseHas('options', [
            'id' => $option->getKey(),
            "name->{$this->lang}" => 'L',
            'schema_id' => $schema->getKey(),
        ]);

        $this->assertDatabaseHas('prices', [
            'value' => "0",
            'currency' => Currency::DEFAULT->value,
            'model_id' => $option->getKey(),
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

        $schema = $this->schemaCrudService->store(FakeDto::schemaDto([
            'name' => 'new schema',
            'description' => 'new schema description',
            'hidden' => false,
            'required' => true,
        ]));

        $item = Item::factory()->create();
        $item2 = Item::factory()->create();

        $option = Option::factory()->create([
            'name' => 'L',
            'schema_id' => $schema->getKey(),
        ]);
        $option->prices()->createMany(Price::factory(['value' => 1000])->prepareForCreateMany());
        $option->items()->sync([
            $item->getKey(),
            $item2->getKey(),
        ]);

        $response = $this->actingAs($this->{$user})->patchJson('/schemas/id:' . $schema->getKey(), []);

        $response->assertValid()->assertOk();

        $this->assertDatabaseHas('schemas', [
            "name->{$this->lang}" => 'new schema',
            "description->{$this->lang}" => 'new schema description',
            'hidden' => false,
            'required' => true,
        ]);

        $this->assertDatabaseHas('prices', [
            'value' => 1000,
            'currency' => Currency::DEFAULT->value,
            'model_id' => $option->getKey(),
            'model_type' => $option->getMorphClass(),
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

        $schema = $this->schemaCrudService->store(FakeDto::schemaDto());

        $schema->metadata()->create([
            'name' => 'first',
            'value' => 'metadata',
            'value_type' => MetadataType::STRING,
            'public' => true,
        ]);

        $response = $this
            ->actingAs($this->{$user})
            ->json('PATCH', '/schemas/id:' . $schema->getKey(), [
                'metadata' => [
                    'first' => 'new value',
                    'second' => 'new metadata',
                ],
            ])
            ->assertValid();

        $response->assertOk()
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
    public function testUpdateNoPublished(string $user): void
    {
        $this->{$user}->givePermissionTo('products.edit');

        $schema = $this->schemaCrudService->store(FakeDto::schemaDto());

        $this
            ->actingAs($this->{$user})
            ->json('PATCH', '/schemas/id:' . $schema->getKey(), [
                'translations' => [
                    $this->lang => [
                        'name' => 'new name',
                    ],
                ],
            ])
            ->assertValid()
            ->assertOk()
            ->assertJsonFragment([
                'name' => 'new name',
                'published' => [
                    $this->lang,
                ],
            ])
            ->assertJsonMissing([
                'first' => 'new value',
            ])
            ->assertJsonMissing([
                'second' => 'new metadata',
            ]);

        $this->assertDatabaseHas('schemas', [
            'id' => $schema->getKey(),
            'published' => json_encode([$this->lang]),
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testRemoveUnauthorized(string $user): void
    {
        $schema = $this->schemaCrudService->store(FakeDto::schemaDto());

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

        $schema = $this->schemaCrudService->store(FakeDto::schemaDto());

        $response = $this->actingAs($this->{$user})
            ->deleteJson('/schemas/id:' . $schema->getKey());

        $response->assertNoContent();
        $this->assertModelMissing($schema);
    }

    public function testPrice(): void
    {
        /** @var Schema $colors */
        $colors = Schema::create([
            'name' => 'Color',
            'type' => SchemaType::SELECT,
        ]);

        /** @var Option $red */
        $red = $colors->options()->create([
            'name' => 'red',
        ]);
        $red->prices()->createMany(Price::factory(['value' => 1000])->prepareForCreateMany());

        /** @var Option $green */
        $green = $colors->options()->create([
            'name' => 'green',
        ]);
        $green->prices()->createMany(Price::factory(['value' => 2000])->prepareForCreateMany());

        /** @var Option $blue */
        $blue = $colors->options()->create([
            'name' => 'blue',
        ]);
        $blue->prices()->createMany(Price::factory(['value' => 3000])->prepareForCreateMany());

        $this->assertEquals(10, $colors->getPrice($red->getKey(), [
            $colors->getKey() => $red->getKey(),
        ], $this->currency)->getAmount()->toFloat());

        $this->assertEquals(20, $colors->getPrice($green->getKey(), [
            $colors->getKey() => $green->getKey(),
        ], $this->currency)->getAmount()->toFloat());

        $this->assertEquals(30, $colors->getPrice($blue->getKey(), [
            $colors->getKey() => $blue->getKey(),
        ], $this->currency)->getAmount()->toFloat());
    }

    public function testRelatedPrice(): void
    {
        /** @var Schema $colors */
        $colors = Schema::create([
            'name' => 'Color',
            'type' => SchemaType::SELECT,
        ]);

        /** @var Option $red */
        $red = $colors->options()->create([
            'name' => 'red',
        ]);
        $red->prices()->createMany(Price::factory(['value' => 1000])->prepareForCreateMany());

        $this->assertEquals(1000, $colors->getPrice($red->getKey(), [
            $colors->getKey() => $red->getKey(),
        ], $this->currency)->getAmount()->toFloat());
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateWithOptionPriceAndDisabledNull(string $user): void
    {
        $this->{$user}->givePermissionTo('products.edit');
        $schema = $this->schemaCrudService->store(FakeDto::schemaDto(), false, false);

        $item = Item::factory()->create();
        $item2 = Item::factory()->create();

        $option = Option::factory()->create([
            'name' => 'L',
            'schema_id' => $schema->getKey(),
        ]);
        $option->prices()->createMany(Price::factory(['value' => 0])->prepareForCreateMany());

        $option->items()->sync([
            $item->getKey(),
            $item2->getKey(),
        ]);

        $data = FakeDto::schemaData([
            'name' => 'Test Updated',
            'description' => 'test test',
            'hidden' => false,
            'required' => false,
            'default' => 0,
            'options' => [
                [
                    'id' => $option->getKey(),
                    'name' => 'L',
                    'items' => [
                        $item->getKey(),
                    ],
                ],
            ],
        ]);
        $data['options'][0]['prices'] = null;

        $response = $this->actingAs($this->{$user})->json('PATCH', '/schemas/id:' . $schema->getKey(), $data);

        $response->assertStatus(422);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateWithOptionPriceAndDisabledNull(string $user): void
    {
        $this->{$user}->givePermissionTo('products.add');
        $item = Item::factory()->create();

        $data = FakeDto::schemaData([
            'name' => 'Test',
            'description' => 'test test',
            'hidden' => false,
            'required' => false,
            'options' => [
                [
                    'name' => 'L',
                    'prices' => null,
                    'items' => [
                        $item->getKey(),
                    ],
                ],
            ],
        ]);
        $data['options'][0]['prices'] = null;

        $response = $this->actingAs($this->{$user})->postJson('/schemas', $data);
        $response->assertStatus(422);
    }
}
