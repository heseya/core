<?php

namespace Tests\Feature;

use App\Enums\AttributeType;
use App\Enums\MetadataType;
use App\Models\Attribute;
use App\Models\AttributeOption;
use Carbon\Carbon;
use Tests\TestCase;

class AttributeTest extends TestCase
{
    private Attribute $attribute;
    private AttributeOption $option;
    private array $newAttribute;
    private array $expectedStructure;
    private array $newOption;

    public function setUp(): void
    {
        parent::setUp();

        $this->attribute = Attribute::factory()->create();

        $this->option = AttributeOption::factory()->create([
            'index' => 1,
            'attribute_id' => $this->attribute->getKey(),
        ]);

        $this->attribute->refresh();

        $this->newAttribute = Attribute::factory()->definition();
        $this->newAttribute['options'] = [
            AttributeOption::factory()->definition(),
            AttributeOption::factory()->definition(),
        ];

        $this->newOption = AttributeOption::factory()->definition();

        $this->expectedStructure = [
            'data' => [
                'name',
                'slug',
                'description',
                'min',
                'max',
                'type',
                'global',
                'sortable',
                'options',
                'metadata',
            ],
        ];
    }

    public function testIndexUnauthorized(): void
    {
        $response = $this->getJson('/attributes');
        $response->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndex($user): void
    {
        $this->$user->givePermissionTo('attributes.show');

        $this->newAttribute['global'] = !$this->attribute->global;
        unset($this->newAttribute['options']);
        Attribute::create($this->newAttribute);

        $this
            ->actingAs($this->$user)
            ->getJson('/attributes')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment([
                'name' => $this->attribute->name,
                'slug' => $this->attribute->slug,
                'description' => $this->attribute->description,
                'type' => $this->attribute->type,
                'global' => $this->attribute->global,
                'sortable' => $this->attribute->sortable,
                'metadata' => [],
            ])
            ->assertJsonFragment([
                'index' => $this->option->index,
                'name' => $this->option->name,
                'value_number' => $this->option->value_number,
                'value_date' => $this->option->value_date,
                'metadata' => [],
            ])
            ->assertJsonFragment($this->newAttribute);
    }

    /**
     * @dataProvider booleanProvider
     */
    public function testIndexGlobalFlagBooleanValues($user, $boolean, $booleanValue): void
    {
        $this->$user->givePermissionTo('attributes.show');

        $this->newAttribute['global'] = $booleanValue;
        unset($this->newAttribute['options']);
        Attribute::create($this->newAttribute);

        $this
            ->actingAs($this->$user)
            ->json('GET', '/attributes', ['global' => $boolean])
            ->assertOk()
            ->assertJsonMissing(['global' => !$booleanValue])
            ->assertJsonFragment($this->newAttribute);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexMetadata($user): void
    {
        $this->$user->givePermissionTo('attributes.show');

        unset($this->newAttribute['options']);
        $attribute = Attribute::create($this->newAttribute);
        $attribute->metadata()->create([
            'name' => 'Dystrybucja',
            'value' => 'Polska',
            'value_type' => MetadataType::STRING,
            'public' => true,
        ]);

        $this
            ->actingAs($this->$user)
            ->getJson('/attributes?metadata[Dystrybucja]=Polska')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(
                array_merge($this->newAttribute, ['Dystrybucja' => 'Polska'])
            );
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexMetadataNotFound($user): void
    {
        $this->$user->givePermissionTo('attributes.show');

        $attribute = Attribute::create($this->newAttribute);
        $attribute->metadata()->create([
            'name' => 'Dystrybucja',
            'value' => 'Francja',
            'value_type' => MetadataType::STRING,
            'public' => true,
        ]);

        $this
            ->actingAs($this->$user)
            ->getJson('/attributes?metadata[Dystrybucja]=Polska')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexMetadataPrivate($user): void
    {
        $this->$user->givePermissionTo(['attributes.show', 'attributes.show_metadata_private']);

        unset($this->newAttribute['options']);
        $attribute = Attribute::create($this->newAttribute);
        $attribute->metadata()->create([
            'name' => 'Dystrybucja',
            'value' => 'Polska',
            'value_type' => MetadataType::STRING,
            'public' => true,
        ]);

        $this
            ->actingAs($this->$user)
            ->getJson('/attributes?metadata[Dystrybucja]=Polska')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(
                array_merge($this->newAttribute, ['Dystrybucja' => 'Polska'])
            );
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexMetadataPrivateNotFound($user): void
    {
        $this->$user->givePermissionTo(['attributes.show', 'attributes.show_metadata_private']);

        $attribute = Attribute::create($this->newAttribute);
        $attribute->metadata()->create([
            'name' => 'Dystrybucja',
            'value' => 'Francja',
            'value_type' => MetadataType::STRING,
            'public' => true,
        ]);

        $this
            ->actingAs($this->$user)
            ->getJson('/attributes?metadata[Dystrybucja]=Polska')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    /**
     * @dataProvider authProvider
     */
    public function testSearch($user): void
    {
        $this->$user->givePermissionTo('attributes.show');
        $first = Attribute::factory()->create(['name' => 'here will by description for test']);
        Attribute::factory()->create([
            'name' => 'new name',
            'description' => 'new description',
        ]);
        Attribute::factory()->create([
            'name' => 'new name test',
            'description' => 'test',
            'slug' => 'description',
        ]);

        Attribute::factory()->create(['name' => 'new name test ' . $first->id]);

        $this
            ->actingAs($this->$user)
            ->json('GET', '/attributes', ['search' => 'description'])
            ->assertOk()
            ->assertJsonCount(3, 'data');

        $this
            ->actingAs($this->$user)
            ->json('GET', '/attributes', ['search' => $first->id])
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    /**
     * @dataProvider authProvider
     */
    public function testSearchNotFound($user): void
    {
        $this->$user->givePermissionTo('attributes.show');

        Attribute::create($this->newAttribute);

        $this
            ->actingAs($this->$user)
            ->json('GET', '/attributes', ['search' => 'abc not found in search'])
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    /**
     * @dataProvider authProvider
     */
    public function testShow($user): void
    {
        $this->$user->givePermissionTo('attributes.show');

        $this
            ->actingAs($this->$user)
            ->getJson('/attributes/id:'. $this->attribute->getKey())
            ->assertOk()
            ->assertJsonFragment([
                'name' => $this->attribute->name,
                'slug' => $this->attribute->slug,
                'description' => $this->attribute->description,
                'type' => $this->attribute->type,
                'global' => $this->attribute->global,
                'sortable' => $this->attribute->sortable,
            ])
            ->assertJsonFragment([
                'index' => $this->option->index,
                'name' => $this->option->name,
                'value_number' => $this->option->value_number,
                'value_date' => $this->option->value_date,
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testShowWrongId($user): void
    {
        $this->$user->givePermissionTo('attributes.show');

        $this
            ->actingAs($this->$user)
            ->getJson('/attributes/id:its-not-uuid')
            ->assertNotFound();

        $this
            ->actingAs($this->$user)
            ->getJson('/attributes/id:'. $this->attribute->getKey() . $this->attribute->getKey())
            ->assertNotFound();
    }

    /**
     * @dataProvider authProvider
     */
    public function testShowMinMaxNumber($user): void
    {
        $this->$user->givePermissionTo('attributes.show');

        $attribute = Attribute::create([
            'name' => 'Monitor screen size',
            'slug' => 'monitor-screen-size',
            'description' => 'MinMax attribute number description',
            'type' => AttributeType::NUMBER,
            'global' => true,
            'sortable' => true,
        ]);

        $option1 = AttributeOption::create([
            'index' => 1,
            'name' => 'Modern screen size',
            'value_number' => 27,
            'value_date' => null,
            'attribute_id' => $attribute->getKey(),
        ]);

        $option2 = AttributeOption::create([
            'index' => 2,
            'name' => 'Old screen size',
            'value_number' => 15,
            'value_date' => null,
            'attribute_id' => $attribute->getKey(),
        ]);

        $this
            ->actingAs($this->$user)
            ->getJson('/attributes/id:'. $attribute->getKey())
            ->assertOk()
            ->assertJsonFragment([
                'name' => $attribute->name,
                'slug' => $attribute->slug,
                'description' => $attribute->description,
                'min' => $option2->value_number,
                'max' => $option1->value_number,
                'type' => $attribute->type,
                'global' => $attribute->global,
                'sortable' => $attribute->sortable,
            ])
            ->assertJsonFragment([
                'index' => $option1->index,
                'name' => $option1->name,
                'value_number' => $option1->value_number,
                'value_date' => $option1->value_date,
                'attribute_id' => $option1->attribute_id,
            ])
            ->assertJsonFragment([
                'index' => $option2->index,
                'name' => $option2->name,
                'value_number' => $option2->value_number,
                'value_date' => $option2->value_date,
                'attribute_id' => $option2->attribute_id,
            ]);

        //checking rest of min/max fields in attribute
        $this->assertDatabaseHas('attributes', [
            'id' => $attribute->getKey(),
            'min_date' => null,
            'max_date' => null,
        ]);
        //making sure to other attributes was not updated by this one
        $this->assertDatabaseMissing('attributes', [
            'id' => $this->attribute->getKey(),
            'min_number' => $option2->value_number,
            'max_number' => $option1->value_number,
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testShowMinMaxDate($user): void
    {
        $this->$user->givePermissionTo('attributes.show');

        $attribute = Attribute::create([
            'name' => 'Release date',
            'slug' => 'release-date',
            'description' => 'MinMax attribute date description',
            'type' => AttributeType::DATE,
            'global' => true,
            'sortable' => true,
        ]);

        $option1 = AttributeOption::create([
            'index' => 1,
            'name' => 'Book #1',
            'value_number' => null,
            'value_date' => '2000-03-25',
            'attribute_id' => $attribute->getKey(),
        ]);

        $option2 = AttributeOption::create([
            'index' => 2,
            'name' => 'Book #2',
            'value_number' => null,
            'value_date' => '1999-02-01',
            'attribute_id' => $attribute->getKey(),
        ]);

        $this
            ->actingAs($this->$user)
            ->getJson('/attributes/id:'. $attribute->getKey())
            ->assertOk()
            ->assertJsonFragment([
                'name' => $attribute->name,
                'slug' => $attribute->slug,
                'description' => $attribute->description,
                'min' => $option2->value_date,
                'max' => $option1->value_date,
                'type' => $attribute->type,
                'global' => $attribute->global,
                'sortable' => $attribute->sortable,
            ])
            ->assertJsonFragment([
                'index' => $option1->index,
                'name' => $option1->name,
                'value_number' => $option1->value_number,
                'value_date' => $option1->value_date,
                'attribute_id' => $option1->attribute_id,
            ])
            ->assertJsonFragment([
                'index' => $option2->index,
                'name' => $option2->name,
                'value_number' => $option2->value_number,
                'value_date' => $option2->value_date,
                'attribute_id' => $option2->attribute_id,
            ]);

        //checking rest of min/max fields in attribute
        $this->assertDatabaseHas('attributes', [
            'id' => $attribute->getKey(),
            'min_number' => null,
            'max_number' => null,
        ]);
        //making sure to other attributes was not updated by this one
        $this->assertDatabaseMissing('attributes', [
            'id' => $this->attribute->getKey(),
            'min_date' => $option2->value_date,
            'max_date' => $option1->value_date,
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreate($user): void
    {
        $this->$user->givePermissionTo('attributes.add');

        $this
            ->actingAs($this->$user)
            ->postJson('/attributes', $this->newAttribute)
            ->assertCreated()
            ->assertJsonStructure($this->expectedStructure)
            ->assertJsonFragment([
                'name' => $this->newAttribute['name'],
                'slug' => $this->newAttribute['slug'],
                'description' => $this->newAttribute['description'],
                'type' => $this->newAttribute['type'],
                'global' => $this->newAttribute['global'],
                'sortable' => $this->newAttribute['sortable'],
            ])
            ->assertJsonFragment(['index' => 1] + $this->newAttribute['options'][0])
            ->assertJsonFragment(['index' => 2] + $this->newAttribute['options'][1]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateWithMetadata($user): void
    {
        $this->$user->givePermissionTo('attributes.add');

        $attribute = Attribute::factory()->make()->toArray();
        $attribute['options'] = [
            AttributeOption::factory()->make(['name' => 'optionOne'])->toArray() + [
                'metadata' => [
                    'optionOne' => 'optionOneValue',
                ],
                'metadata_private' => [
                    'optionOnePriv' => 'optionOneValuePriv',
                ],
            ],
            AttributeOption::factory()->make(['name' => 'optionTwo'])->toArray() + [
                'metadata' => [
                    'optionTwo' => 'optionTwoValue',
                ],
            ],
        ];

        $response = $this->actingAs($this->$user)
            ->postJson('/attributes', $attribute + [
                'metadata' => [
                    'attributeMeta' => 'attributeValueOne',
                ],
                'metadata_private' => [
                    'attributeMetaPriv' => 'attributeValueOnePriv',
                ],
            ]);

        $createdAttribute = Attribute::find($response->getData()->data->id);
        $optionOne = $createdAttribute->options()->where('name', 'optionOne')->first();
        $optionTwo = $createdAttribute->options()->where('name', 'optionTwo')->first();

        $this->assertDatabaseCount('metadata', 5)
            ->assertDatabaseHas('metadata', [
                'name' => 'attributeMeta',
                'value' => 'attributeValueOne',
                'model_id' => $createdAttribute->getKey(),
                'public' => true,
            ])
            ->assertDatabaseHas('metadata', [
                'name' => 'attributeMetaPriv',
                'value' => 'attributeValueOnePriv',
                'model_id' => $createdAttribute->getKey(),
                'public' => false,
            ])
            ->assertDatabaseHas('metadata', [
                'name' => 'optionOne',
                'value' => 'optionOneValue',
                'model_id' => $optionOne->getKey(),
                'public' => true,
            ])
            ->assertDatabaseHas('metadata', [
                'name' => 'optionOnePriv',
                'value' => 'optionOneValuePriv',
                'model_id' => $optionOne->getKey(),
                'public' => false,
            ])
            ->assertDatabaseHas('metadata', [
                'name' => 'optionTwo',
                'value' => 'optionTwoValue',
                'model_id' => $optionTwo->getKey(),
                'public' => true,
            ]);
    }

    /**
     * @dataProvider booleanProvider
     */
    public function testCreateBooleanValues($user, $boolean, $booleanValue): void
    {
        $this->$user->givePermissionTo('attributes.add');

        $this
            ->actingAs($this->$user)
            ->postJson('/attributes', array_merge($this->newAttribute, ['global' => $boolean, 'sortable' => $boolean]))
            ->assertCreated()
            ->assertJsonStructure($this->expectedStructure)
            ->assertJsonFragment([
                'name' => $this->newAttribute['name'],
                'slug' => $this->newAttribute['slug'],
                'description' => $this->newAttribute['description'],
                'type' => $this->newAttribute['type'],
                'global' => $booleanValue,
                'sortable' => $booleanValue,
            ])
            ->assertJsonFragment(['index' => 1] + $this->newAttribute['options'][0])
            ->assertJsonFragment(['index' => 2] + $this->newAttribute['options'][1]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateSingleOptionAndOptionWithoutName($user): void
    {
        $this->$user->givePermissionTo('attributes.add');

        $this->newAttribute['type'] = AttributeType::SINGLE_OPTION;
        unset($this->newAttribute['options']);
        unset($this->newOption['name']);
        $this->newAttribute['options'] = [$this->newOption];

        $this
            ->actingAs($this->$user)
            ->postJson('/attributes', $this->newAttribute)
            ->assertUnprocessable();
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateWithInvalidValueNumber($user): void
    {
        $this->$user->givePermissionTo('attributes.add');

        $attribute = Attribute::factory()->make([
            'type' => AttributeType::SINGLE_OPTION,
        ]);
        $attribute['options'] = [
            AttributeOption::factory()->make([
                'value_number' => 9999999.99,
            ]),
        ];

        $response = $this
            ->actingAs($this->$user)
            ->postJson('/attributes', $attribute->toArray());

        $response->assertUnprocessable();
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateIncompleteData($user): void
    {
        $this->$user->givePermissionTo('attributes.add');

        unset($this->newAttribute['name']);

        $this
            ->actingAs($this->$user)
            ->postJson('/attributes', $this->newAttribute)
            ->assertUnprocessable();
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateUnauthorized($user): void
    {
        $this
            ->actingAs($this->$user)
            ->postJson('/attributes', $this->newAttribute)
            ->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdate($user): void
    {
        $this->$user->givePermissionTo('attributes.edit');

        $attributeUpdate = [
            'name' => 'Test ' . $this->attribute->name,
            'slug' => 'test-' . $this->attribute->slug,
            'description' => 'Test ' . $this->attribute->description,
            'type' => $this->attribute->type,
            'global' => true,
            'sortable' => true,
            'options' => [
                [
                    'id' => $this->option->getKey(),
                    'name' => 'Test ' . $this->option->name,
                    'value_number' => $this->option->value_number,
                    'value_date' => $this->option->value_date,
                ],
            ],
        ];

        $this
            ->actingAs($this->$user)
            ->patchJson('/attributes/id:' . $this->attribute->getKey(), $attributeUpdate)
            ->assertOk()
            ->assertJsonStructure($this->expectedStructure)
            ->assertJsonFragment([
                'name' => $attributeUpdate['name'],
                'slug' => $attributeUpdate['slug'],
                'description' => $attributeUpdate['description'],
                'type' => $attributeUpdate['type'],
                'global' => $attributeUpdate['global'],
                'sortable' => $attributeUpdate['sortable'],
            ])
            ->assertJsonFragment($attributeUpdate['options'][0]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateWithoutSlug($user): void
    {
        $this->$user->givePermissionTo('attributes.edit');

        $attributeUpdate = [
            'name' => 'Test ' . $this->attribute->name,
            'slug' => $this->attribute->slug,
            'description' => 'Test ' . $this->attribute->description,
            'type' => $this->attribute->type,
            'global' => true,
            'sortable' => true,
            'options' => [
                [
                    'id' => $this->option->getKey(),
                    'name' => 'Test ' . $this->option->name,
                    'value_number' => $this->option->value_number,
                    'value_date' => $this->option->value_date,
                ],
            ],
        ];

        $this
            ->actingAs($this->$user)
            ->patchJson('/attributes/id:' . $this->attribute->getKey(), $attributeUpdate)
            ->assertOk()
            ->assertJsonStructure($this->expectedStructure)
            ->assertJsonFragment([
                'name' => $attributeUpdate['name'],
                'slug' => $attributeUpdate['slug'],
                'description' => $attributeUpdate['description'],
                'type' => $attributeUpdate['type'],
                'global' => $attributeUpdate['global'],
                'sortable' => $attributeUpdate['sortable'],
            ])
            ->assertJsonFragment($attributeUpdate['options'][0]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateChangeType($user): void
    {
        $this->$user->givePermissionTo('attributes.edit');

        while (true) {
            $randomType = AttributeType::getRandomValue();

            if ($randomType !== $this->attribute->type->value) {
                $this->attribute->type = $randomType;
                break;
            }
        }

        $attributeUpdate = [
            'name' => 'Test ' . $this->attribute->name,
            'slug' => $this->attribute->slug,
            'description' => 'Test ' . $this->attribute->description,
            'type' => $this->attribute->type,
            'global' => true,
            'sortable' => true,
            'options' => [
                [
                    'id' => $this->option->getKey(),
                    'name' => 'Test ' . $this->option->name,
                    'value_number' => $this->option->value_number,
                    'value_date' => $this->option->value_date,
                ],
            ],
        ];

        $this
            ->actingAs($this->$user)
            ->patchJson('/attributes/id:' . $this->attribute->getKey(), $attributeUpdate)
            ->assertUnprocessable();
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateIncompleteData($user): void
    {
        $this->$user->givePermissionTo('attributes.edit');

        $attributeUpdate = [
            'name' => 'Test update attribute name',
        ];

        $this
            ->actingAs($this->$user)
            ->patchJson('/attributes/id:' . $this->attribute->getKey(), $attributeUpdate)
            ->assertUnprocessable();
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateNotExistingAttribute($user): void
    {
        $this->$user->givePermissionTo('attributes.edit');

        Attribute::destroy($this->attribute->getKey());

        $attributeUpdate = [
            'name' => 'Test update attribute name',
        ];

        $this
            ->actingAs($this->$user)
            ->patchJson('/attributes/id:' . $this->attribute->getKey(), $attributeUpdate)
            ->assertNotFound();
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateWithoutAssignedOption($user): void
    {
        $this->$user->givePermissionTo('attributes.edit');

        $attributeUpdate = [
            'name' => 'Test ' . $this->attribute->name,
            'slug' => 'test-' . $this->attribute->slug,
            'description' => 'Test ' . $this->attribute->description,
            'type' => $this->attribute->type,
            'global' => true,
            'sortable' => true,
            'options' => [
                [
                    'name' => 'Totally different option',
                    'value_number' => $this->option->value_number,
                    'value_date' => $this->option->value_date,
                ],
            ],
        ];

        $this
            ->actingAs($this->$user)
            ->patchJson('/attributes/id:' . $this->attribute->getKey(), $attributeUpdate)
            ->assertOk()
            ->assertJsonStructure($this->expectedStructure)
            ->assertJsonFragment([
                'name' => $attributeUpdate['name'],
                'slug' => $attributeUpdate['slug'],
                'description' => $attributeUpdate['description'],
                'type' => $attributeUpdate['type'],
                'global' => $attributeUpdate['global'],
                'sortable' => $attributeUpdate['sortable'],
            ])
            ->assertJsonFragment($attributeUpdate['options'][0])
            ->assertJsonMissing(['id' => $this->option->getKey()]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateUnauthorized($user): void
    {
        $attributeUpdate = [
            'name' => 'Test ' . $this->attribute->name,
            'slug' => 'test-' . $this->attribute->slug,
            'description' => 'Test ' . $this->attribute->description,
            'type' => AttributeType::NUMBER,
            'global' => true,
            'sortable' => true,
            'options' => [
                [
                    'id' => $this->option->id,
                    'name' => 'Test ' . $this->option->name,
                    'value_number' => $this->option->value_number,
                    'value_date' => $this->option->value_date,
                ],
            ],
        ];

        $this
            ->actingAs($this->$user)
            ->patchJson('/attributes/id:' . $this->attribute->getKey(), $attributeUpdate)
            ->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testDelete($user): void
    {
        $this->$user->givePermissionTo('attributes.remove');

        $this
            ->actingAs($this->$user)
            ->deleteJson('/attributes/id:' . $this->attribute->getKey())
            ->assertNoContent();

        $this->assertDatabaseMissing('attributes', [
            'id' => $this->attribute->getKey(),
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testDeleteNotExistingAttribute($user): void
    {
        $this->$user->givePermissionTo('attributes.remove');

        Attribute::destroy($this->attribute->getKey());

        $this
            ->actingAs($this->$user)
            ->deleteJson('/attributes/id:' . $this->attribute->getKey())
            ->assertNotFound();
    }

    /**
     * @dataProvider authProvider
     */
    public function testDeleteUnauthorized($user): void
    {
        $this
            ->actingAs($this->$user)
            ->deleteJson('/attributes/id:' . $this->attribute->getKey())
            ->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexOptions($user): void
    {
        $this->$user->givePermissionTo('attributes.show');

        $this
            ->actingAs($this->$user)
            ->getJson("/attributes/id:{$this->attribute->getKey()}/options")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment([
                'id' => $this->option->getKey(),
                'name' => $this->option->name,
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexOptionsWrongId($user): void
    {
        $this->$user->givePermissionTo('attributes.show');

        $this
            ->actingAs($this->$user)
            ->getJson('/attributes/id:its-not-uuid/options')
            ->assertNotFound();

        $this
            ->actingAs($this->$user)
            ->getJson("/attributes/id:{$this->attribute->getKey()}{$this->attribute->getKey()}/options")
            ->assertNotFound();
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexOptionsMetadata($user): void
    {
        $this->$user->givePermissionTo('attributes.show');

        $option = AttributeOption::create(
            $this->newOption +
            [
                'index' => 1,
                'attribute_id' => $this->attribute->getKey(),
            ]
        );
        $option->metadata()->create([
            'name' => 'Dystrybucja',
            'value' => 'Polska',
            'value_type' => MetadataType::STRING,
            'public' => true,
        ]);

        $this
            ->actingAs($this->$user)
            ->getJson("/attributes/id:{$this->attribute->getKey()}/options?metadata[Dystrybucja]=Polska")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(
                array_merge($this->newOption, ['Dystrybucja' => 'Polska'])
            );
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexOptionsMetadataNotFound($user): void
    {
        $this->$user->givePermissionTo('attributes.show');

        $option = AttributeOption::create(
            $this->newOption +
            [
                'index' => 1,
                'attribute_id' => $this->attribute->getKey(),
            ]
        );
        $option->metadata()->create([
            'name' => 'Dystrybucja',
            'value' => 'Francja',
            'value_type' => MetadataType::STRING,
            'public' => true,
        ]);

        $this
            ->actingAs($this->$user)
            ->getJson("/attributes/id:{$this->attribute->getKey()}/options?metadata[Dystrybucja]=Polska")
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexOptionsMetadataPrivate($user): void
    {
        $this->$user->givePermissionTo(['attributes.show', 'attributes.show_metadata_private']);

        $option = AttributeOption::create(
            $this->newOption +
            [
                'index' => 1,
                'attribute_id' => $this->attribute->getKey(),
            ]
        );
        $option->metadata()->create([
            'name' => 'Dystrybucja',
            'value' => 'Polska',
            'value_type' => MetadataType::STRING,
            'public' => true,
        ]);

        $this
            ->actingAs($this->$user)
            ->getJson("/attributes/id:{$this->attribute->getKey()}/options?metadata[Dystrybucja]=Polska")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(
                array_merge($this->newOption, ['Dystrybucja' => 'Polska'])
            );
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexOptionsMetadataPrivateNotFound($user): void
    {
        $this->$user->givePermissionTo(['attributes.show', 'attributes.show_metadata_private']);

        $option = AttributeOption::create(
            $this->newOption +
            [
                'index' => 1,
                'attribute_id' => $this->attribute->getKey(),
            ]
        );
        $option->metadata()->create([
            'name' => 'Dystrybucja',
            'value' => 'Francja',
            'value_type' => MetadataType::STRING,
            'public' => true,
        ]);

        $this
            ->actingAs($this->$user)
            ->getJson("/attributes/id:{$this->attribute->getKey()}/options?metadata[Dystrybucja]=Polska")
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    /**
     * @dataProvider authProvider
     */
    public function testAddOption($user): void
    {
        $this->$user->givePermissionTo('attributes.edit');

        $this
            ->actingAs($this->$user)
            ->postJson('/attributes/id:' . $this->attribute->getKey() . '/options', $this->newOption)
            ->assertCreated()
            ->assertJsonFragment($this->newOption);

        $this->assertDatabaseHas('attribute_options', $this->newOption);
    }

    /**
     * @dataProvider authProvider
     */
    public function testAddOptionWithMetadata($user): void
    {
        $this->$user->givePermissionTo('attributes.edit');

        $response = $this->actingAs($this->$user)
            ->postJson('/attributes/id:' . $this->attribute->getKey() . '/options', $this->newOption + [
                'metadata' => [
                    'optionMeta' => 'testValue',
                ],
            ])
            ->assertCreated()
            ->assertJsonFragment($this->newOption);

        $this->assertDatabaseHas('attribute_options', $this->newOption)
            ->assertDatabaseCount('metadata', 1)
            ->assertDatabaseHas('metadata', [
                'name' => 'optionMeta',
                'value' => 'testValue',
                'model_id' => $response->getData()->data->id,
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testAddOptionNumberWithoutName($user): void
    {
        $this->$user->givePermissionTo('attributes.edit');

        $attribute = Attribute::factory([
            'type' => AttributeType::NUMBER,
        ])->create();
        unset($this->newOption['name']);

        $this
            ->actingAs($this->$user)
            ->postJson('/attributes/id:' . $attribute->getKey() . '/options', $this->newOption)
            ->assertCreated()
            ->assertJsonFragment($this->newOption);

        $this->assertDatabaseHas('attribute_options', $this->newOption);
    }

    /**
     * @dataProvider authProvider
     */
    public function testAddOptionIncompleteData($user): void
    {
        $this->$user->givePermissionTo('attributes.edit');

        $attribute = Attribute::factory([
            'type' => AttributeType::SINGLE_OPTION,
        ])->create();
        unset($this->newOption['name']);

        $this
            ->actingAs($this->$user)
            ->postJson('/attributes/id:' . $attribute->getKey() . '/options', $this->newOption)
            ->assertUnprocessable();
    }

    /**
     * @dataProvider authProvider
     */
    public function testAddOptionToDeletedAttribute($user): void
    {
        $this->$user->givePermissionTo('attributes.edit');

        Attribute::destroy($this->attribute->getKey());

        $this
            ->actingAs($this->$user)
            ->postJson('/attributes/id:' . $this->attribute->getKey() . '/options', $this->newOption)
            ->assertNotFound();
    }

    /**
     * @dataProvider authProvider
     */
    public function testAddOptionUnauthorized($user): void
    {
        $this
            ->actingAs($this->$user)
            ->postJson('/attributes/id:' . $this->attribute->getKey() . '/options', $this->newOption)
            ->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateOption($user): void
    {
        $this->$user->givePermissionTo('attributes.edit');

        $optionUpdate = [
            'id' => $this->option->id,
            'name' => 'Test ' . $this->option->name,
            'value_number' => $this->option->value_number + 1,
            'value_date' => Carbon::now()->toDateString(),
            'attribute_id' => $this->option->attribute_id,
        ];

        $this
            ->actingAs($this->$user)
            ->json(
                'PATCH',
                '/attributes/id:' . $this->attribute->getKey() . '/options/id:'. $this->option->getKey(),
                $optionUpdate
            )
            ->assertOk()
            ->assertJsonFragment($optionUpdate);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateOptionWithoutId($user): void
    {
        $this->$user->givePermissionTo('attributes.edit');

        $optionUpdate = [
            'name' => 'Test ' . $this->option->name,
            'value_number' => $this->option->value_number + 1,
            'value_date' => Carbon::now()->toDateString(),
            'attribute_id' => $this->option->attribute_id,
        ];

        $this
            ->actingAs($this->$user)
            ->json(
                'PATCH',
                '/attributes/id:' . $this->attribute->getKey() . '/options/id:'. $this->option->getKey(),
                $optionUpdate
            )
            ->assertOk()
            ->assertJsonFragment($optionUpdate);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateOptionIncompleteData($user): void
    {
        $this->$user->givePermissionTo('attributes.edit');

        $attribute = Attribute::factory([
            'type' => AttributeType::SINGLE_OPTION,
        ])->create();

        $option = AttributeOption::factory()->create([
            'index' => 1,
            'attribute_id' => $attribute->getKey(),
        ]);

        $optionUpdate = [
            'value_number' => $option->value_number + 1,
            'value_date' => Carbon::now()->toDateString(),
            'attribute_id' => $option->attribute_id,
        ];

        $this
            ->actingAs($this->$user)
            ->json(
                'PATCH',
                '/attributes/id:' . $attribute->getKey() . '/options/id:'. $option->getKey(),
                $optionUpdate
            )
            ->assertUnprocessable();
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateOptionNotExisting($user): void
    {
        $this->$user->givePermissionTo('attributes.edit');

        $optionUpdate = [
            'id' => $this->option->id,
            'name' => 'Test ' . $this->option->name,
            'value_number' => $this->option->value_number + 1,
            'value_date' => Carbon::now()->toDateString(),
            'attribute_id' => $this->option->attribute_id,
        ];

        $this->option->delete();

        $this
            ->actingAs($this->$user)
            ->json(
                'PATCH',
                '/attributes/id:' . $this->attribute->getKey() . '/options/id:'. $this->option->getKey(),
                $optionUpdate
            )
            ->assertNotFound();
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateOptionNotRelatedOption($user): void
    {
        $this->$user->givePermissionTo('attributes.edit');

        $attribute = Attribute::factory()->create();

        $optionUpdate = [
            'id' => $this->option->id,
            'name' => 'Test ' . $this->option->name,
            'value_number' => $this->option->value_number + 1,
            'value_date' => Carbon::now()->toDateString(),
            'attribute_id' => $this->option->attribute_id,
        ];

        $this
            ->actingAs($this->$user)
            ->json(
                'PATCH',
                '/attributes/id:' . $attribute->getKey() . '/options/id:'. $this->option->getKey(),
                $optionUpdate
            )
            ->assertNotFound();
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateOptionUnauthorized($user): void
    {
        $optionUpdate = [
            'id' => $this->option->id,
            'name' => 'Test ' . $this->option->name,
            'value_number' => $this->option->value_number + 1,
            'value_date' => Carbon::now()->toDateString(),
            'attribute_id' => $this->option->attribute_id,
        ];

        $this
            ->actingAs($this->$user)
            ->patchJson(
                '/attributes/id:' . $this->attribute->getKey() . '/options/id:'. $this->option->getKey(),
                $optionUpdate
            )
            ->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testDeleteOption($user): void
    {
        $this->$user->givePermissionTo('attributes.edit');

        $this
            ->actingAs($this->$user)
            ->deleteJson('/attributes/id:' . $this->attribute->getKey() . '/options/id:'. $this->option->getKey())
            ->assertNoContent();

        $this->assertSoftDeleted($this->option);
    }

    /**
     * @dataProvider authProvider
     */
    public function testDeleteOptionNotExisting($user): void
    {
        $this->$user->givePermissionTo('attributes.edit');

        $this->option->delete();

        $this
            ->actingAs($this->$user)
            ->deleteJson('/attributes/id:' . $this->attribute->getKey() . '/options/id:'. $this->option->getKey())
            ->assertNotFound();
    }

    /**
     * @dataProvider authProvider
     */
    public function testDeleteOptionNotRelatedOption($user): void
    {
        $this->$user->givePermissionTo('attributes.edit');

        $attribute = Attribute::factory()->create();

        $this
            ->actingAs($this->$user)
            ->deleteJson('/attributes/id:' . $attribute->getKey() . '/options/id:'. $this->option->getKey())
            ->assertNotFound();
    }

    /**
     * @dataProvider authProvider
     */
    public function testDeleteOptionUnauthorized($user): void
    {
        $this
            ->actingAs($this->$user)
            ->deleteJson('/attributes/id:' . $this->attribute->getKey() . '/options/id:'. $this->option->getKey())
            ->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testIncrementIndex($user): void
    {
        $this->$user->givePermissionTo(['attributes.show', 'attributes.edit', 'attributes.add']);

        $response = $this
            ->actingAs($this->$user)
            ->postJson('/attributes', $this->newAttribute)
            ->assertCreated()
            ->assertJsonStructure($this->expectedStructure)
            ->assertJsonFragment([
                'name' => $this->newAttribute['name'],
                'slug' => $this->newAttribute['slug'],
                'description' => $this->newAttribute['description'],
                'type' => $this->newAttribute['type'],
                'global' => $this->newAttribute['global'],
                'sortable' => $this->newAttribute['sortable'],
            ])
            ->assertJsonFragment(['index' => 1] + $this->newAttribute['options'][0])
            ->assertJsonFragment(['index' => 2] + $this->newAttribute['options'][1]);

        AttributeOption::query()
            ->where('attribute_id', '=', $response['data']['id'])
            ->where('index', '=', 2)
            ->delete();

        $this->assertSoftDeleted('attribute_options', [
            'attribute_id' => $response['data']['id'],
            'index' => 2,
        ]);

        $this
            ->actingAs($this->$user)
            ->postJson('/attributes/id:' . $response['data']['id'] . '/options', $this->newOption)
            ->assertCreated()
            ->assertJsonFragment(['index' => 3] + $this->newOption);

        $this
            ->actingAs($this->$user)
            ->getJson('/attributes/id:' . $response['data']['id'])
            ->assertOk()
            ->assertJsonFragment([
                'name' => $this->newAttribute['name'],
                'slug' => $this->newAttribute['slug'],
                'description' => $this->newAttribute['description'],
                'type' => $this->newAttribute['type'],
                'global' => $this->newAttribute['global'],
                'sortable' => $this->newAttribute['sortable'],
            ])
            ->assertJsonFragment(['index' => 1])
            ->assertJsonMissing(['index' => 2])
            ->assertJsonFragment(['index' => 3]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateMinMaxNumberOnUpdateOption($user): void
    {
        $this->$user->givePermissionTo('attributes.show');

        $attribute = Attribute::factory([
            'type' => AttributeType::NUMBER,
        ])->create();

        $option1 = AttributeOption::factory()->create([
            'index' => 1,
            'value_number' => 100,
            'attribute_id' => $attribute->getKey(),
        ]);

        $option2 = AttributeOption::factory()->create([
            'index' => 1,
            'value_number' => 200,
            'attribute_id' => $attribute->getKey(),
        ]);

        $option1->update(['value_number' => 110]);
        $option2->update(['value_number' => 190]);

        $this
            ->actingAs($this->$user)
            ->getJson('/attributes/id:'. $attribute->getKey())
            ->assertOk()
            ->assertJsonFragment([
                'min' => 110,
                'max' => 190,
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateMinMaxNumberOnDeleteOption($user): void
    {
        $this->$user->givePermissionTo('attributes.show');

        $attribute = Attribute::factory([
            'type' => AttributeType::NUMBER,
        ])->create();

        AttributeOption::factory()->create([
            'index' => 1,
            'value_number' => 100,
            'attribute_id' => $attribute->getKey(),
        ]);

        $option2 = AttributeOption::factory()->create([
            'index' => 1,
            'value_number' => 200,
            'attribute_id' => $attribute->getKey(),
        ]);

        $option2->delete();

        $this
            ->actingAs($this->$user)
            ->getJson('/attributes/id:'. $attribute->getKey())
            ->assertOk()
            ->assertJsonFragment([
                'min' => 100,
                'max' => 100,
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateMinMaxDateOnUpdateOption($user): void
    {
        $this->$user->givePermissionTo('attributes.show');

        $attribute = Attribute::factory([
            'type' => AttributeType::DATE,
        ])->create();

        $option1 = AttributeOption::factory()->create([
            'index' => 1,
            'value_date' => '2010-03-15',
            'attribute_id' => $attribute->getKey(),
        ]);

        $option2 = AttributeOption::factory()->create([
            'index' => 1,
            'value_date' => '2020-03-15',
            'attribute_id' => $attribute->getKey(),
        ]);

        $option1->update(['value_date' => '2012-08-10']);
        $option2->update(['value_date' => '2019-01-01']);

        $this
            ->actingAs($this->$user)
            ->getJson('/attributes/id:'. $attribute->getKey())
            ->assertOk()
            ->assertJsonFragment([
                'min' => '2012-08-10',
                'max' => '2019-01-01',
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateMinMaxDateOnDeleteOption($user): void
    {
        $this->$user->givePermissionTo('attributes.show');

        $attribute = Attribute::factory([
            'type' => AttributeType::DATE,
        ])->create();

        AttributeOption::factory()->create([
            'index' => 1,
            'value_date' => '2010-03-15',
            'attribute_id' => $attribute->getKey(),
        ]);

        $option2 = AttributeOption::factory()->create([
            'index' => 1,
            'value_date' => '2020-03-15',
            'attribute_id' => $attribute->getKey(),
        ]);

        $option2->delete();

        $this
            ->actingAs($this->$user)
            ->getJson('/attributes/id:'. $attribute->getKey())
            ->assertOk()
            ->assertJsonFragment([
                'min' => '2010-03-15',
                'max' => '2010-03-15',
            ]);
    }
}
