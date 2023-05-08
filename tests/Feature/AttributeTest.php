<?php

namespace Tests\Feature;

use App\Enums\AttributeType;
use App\Enums\MetadataType;
use App\Models\Attribute;
use App\Models\AttributeOption;
use App\Models\Option;
use Illuminate\Support\Carbon;
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
    public function testIndex(string $user): void
    {
        $this->$user->givePermissionTo('attributes.show');

        $this->newAttribute['global'] = !$this->attribute->global;
        unset($this->newAttribute['options']);
        Attribute::query()->create($this->newAttribute);

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
            ->assertJsonFragment($this->newAttribute);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexByIds(string $user): void
    {
        $this->$user->givePermissionTo('attributes.show');

        $this->newAttribute['global'] = !$this->attribute->global;
        unset($this->newAttribute['options']);
        Attribute::query()->create($this->newAttribute);

        $this
            ->actingAs($this->$user)
            ->json('GET', '/attributes', [
                'ids' => [
                    $this->attribute->getKey(),
                ],
            ])
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment([
                'name' => $this->attribute->name,
                'slug' => $this->attribute->slug,
                'description' => $this->attribute->description,
                'type' => $this->attribute->type,
                'global' => $this->attribute->global,
                'sortable' => $this->attribute->sortable,
                'metadata' => [],
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexMetadata(string $user): void
    {
        $this->$user->givePermissionTo('attributes.show');

        unset($this->newAttribute['options']);

        /** @var Attribute $attribute */
        $attribute = Attribute::query()->create($this->newAttribute);
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
    public function testIndexMetadataNotFound(string $user): void
    {
        $this->$user->givePermissionTo('attributes.show');

        /** @var Attribute $attribute */
        $attribute = Attribute::query()->create($this->newAttribute);
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
    public function testIndexMetadataPrivate(string $user): void
    {
        $this->$user->givePermissionTo(['attributes.show', 'attributes.show_metadata_private']);

        unset($this->newAttribute['options']);

        /** @var Attribute $attribute */
        $attribute = Attribute::query()->create($this->newAttribute);
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
    public function testIndexMetadataPrivateNotFound(string $user): void
    {
        $this->$user->givePermissionTo(['attributes.show', 'attributes.show_metadata_private']);

        /** @var Attribute $attribute */
        $attribute = Attribute::query()->create($this->newAttribute);
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
    public function testSearch(string $user): void
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
    public function testSearchNotFound(string $user): void
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
    public function testShow(string $user): void
    {
        $this->$user->givePermissionTo('attributes.show');

        $this
            ->actingAs($this->$user)
            ->getJson('/attributes/id:' . $this->attribute->getKey())
            ->assertOk()
            ->assertJsonFragment([
                'name' => $this->attribute->name,
                'slug' => $this->attribute->slug,
                'description' => $this->attribute->description,
                'type' => $this->attribute->type,
                'global' => $this->attribute->global,
                'sortable' => $this->attribute->sortable,
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testShowWrongId(string $user): void
    {
        $this->$user->givePermissionTo('attributes.show');

        $this
            ->actingAs($this->$user)
            ->getJson('/attributes/id:its-not-uuid')
            ->assertNotFound();

        $this
            ->actingAs($this->$user)
            ->getJson('/attributes/id:' . $this->attribute->getKey() . $this->attribute->getKey())
            ->assertNotFound();
    }

    /**
     * @dataProvider authProvider
     */
    public function testShowMinMaxNumber(string $user): void
    {
        $this->$user->givePermissionTo('attributes.show');

        /** @var Attribute $attribute */
        $attribute = Attribute::query()->create([
            'name' => 'Monitor screen size',
            'slug' => 'monitor-screen-size',
            'description' => 'MinMax attribute number description',
            'type' => AttributeType::NUMBER,
            'global' => true,
            'sortable' => true,
        ]);

        /** @var AttributeOption $option1 */
        $option1 = AttributeOption::query()->create([
            'index' => 1,
            'name' => 'Modern screen size',
            'value_number' => 27,
            'value_date' => null,
            'attribute_id' => $attribute->getKey(),
        ]);

        /** @var AttributeOption $option2 */
        $option2 = AttributeOption::query()->create([
            'index' => 2,
            'name' => 'Old screen size',
            'value_number' => 15,
            'value_date' => null,
            'attribute_id' => $attribute->getKey(),
        ]);

        $this
            ->actingAs($this->$user)
            ->getJson('/attributes/id:' . $attribute->getKey())
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
    public function testShowMinMaxDate(string $user): void
    {
        $this->$user->givePermissionTo('attributes.show');

        /** @var Attribute $attribute */
        $attribute = Attribute::query()->create([
            'name' => 'Release date',
            'slug' => 'release-date',
            'description' => 'MinMax attribute date description',
            'type' => AttributeType::DATE,
            'global' => true,
            'sortable' => true,
        ]);

        /** @var AttributeOption $option1 */
        $option1 = AttributeOption::query()->create([
            'index' => 1,
            'name' => 'Book #1',
            'value_number' => null,
            'value_date' => '2000-03-25',
            'attribute_id' => $attribute->getKey(),
        ]);

        /** @var AttributeOption $option2 */
        $option2 = AttributeOption::query()->create([
            'index' => 2,
            'name' => 'Book #2',
            'value_number' => null,
            'value_date' => '1999-02-01',
            'attribute_id' => $attribute->getKey(),
        ]);

        $this
            ->actingAs($this->$user)
            ->getJson('/attributes/id:' . $attribute->getKey())
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
    public function testCreate(string $user): void
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
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateWithMetadata(string $user): void
    {
        $this->$user->givePermissionTo('attributes.add');

        $attribute = Attribute::factory()->make()->toArray();

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

        $this->assertDatabaseCount('metadata', 2)
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
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateSingleOptionAndOptionWithoutName(string $user): void
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
    public function testCreateWithInvalidValueNumber(string $user): void
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
    public function testCreateIncompleteData(string $user): void
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
    public function testCreateUnauthorized(string $user): void
    {
        $this
            ->actingAs($this->$user)
            ->postJson('/attributes', $this->newAttribute)
            ->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdate(string $user): void
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
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateWithoutSlug(string $user): void
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
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateChangeType(string $user): void
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
    public function testUpdateIncompleteData(string $user): void
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
    public function testUpdateNotExistingAttribute(string $user): void
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
    public function testUpdateUnauthorized(string $user): void
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
    public function testDelete(string $user): void
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
    public function testDeleteNotExistingAttribute(string $user): void
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
    public function testDeleteUnauthorized(string $user): void
    {
        $this
            ->actingAs($this->$user)
            ->deleteJson('/attributes/id:' . $this->attribute->getKey())
            ->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexOptions(string $user): void
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

    public function testIndexOptionsUnauthorized(): void
    {
        $this
            ->getJson("/attributes/id:{$this->attribute->getKey()}/options")
            ->assertForbidden()
            ->assertJsonFragment(['message' => 'This action is unauthorized.']);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexOptionsWithPagination(string $user): void
    {
        $this->$user->givePermissionTo('attributes.show');

        AttributeOption::factory()
            ->count(20)
            ->sequence(fn ($sequence) => ['index' => $sequence->index])
            ->create([
                'attribute_id' => $this->attribute->getKey(),
            ]);

        $this
            ->actingAs($this->$user)
            ->json('GET', "/attributes/id:{$this->attribute->getKey()}/options", ['limit' => 10])
            ->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonFragment([
                'per_page' => 10,
                'to' => 10,
                'total' => 21,
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexOptionsWrongId(string $user): void
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
    public function testIndexOptionsMetadata(string $user): void
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
    public function testIndexOptionsMetadataNotFound(string $user): void
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
    public function testIndexOptionsMetadataPrivate(string $user): void
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
    public function testIndexOptionsMetadataPrivateNotFound(string $user): void
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
    public function testAddOption(string $user): void
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
    public function testAddOptionWithMetadata(string $user): void
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
    public function testAddOptionNumberWithoutName(string $user): void
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
    public function testAddOptionIncompleteData(string $user): void
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
    public function testAddOptionToDeletedAttribute(string $user): void
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
    public function testAddOptionUnauthorized(string $user): void
    {
        $this
            ->actingAs($this->$user)
            ->postJson('/attributes/id:' . $this->attribute->getKey() . '/options', $this->newOption)
            ->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateOption(string $user): void
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
                '/attributes/id:' . $this->attribute->getKey() . '/options/id:' . $this->option->getKey(),
                $optionUpdate
            )
            ->assertOk()
            ->assertJsonFragment($optionUpdate);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateOptionWithoutId(string $user): void
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
                '/attributes/id:' . $this->attribute->getKey() . '/options/id:' . $this->option->getKey(),
                $optionUpdate
            )
            ->assertOk()
            ->assertJsonFragment($optionUpdate);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateOptionIncompleteData(string $user): void
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
                '/attributes/id:' . $attribute->getKey() . '/options/id:' . $option->getKey(),
                $optionUpdate
            )
            ->assertUnprocessable();
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateOptionNotExisting(string $user): void
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
                '/attributes/id:' . $this->attribute->getKey() . '/options/id:' . $this->option->getKey(),
                $optionUpdate
            )
            ->assertNotFound();
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateOptionNotRelatedOption(string $user): void
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
                '/attributes/id:' . $attribute->getKey() . '/options/id:' . $this->option->getKey(),
                $optionUpdate
            )
            ->assertNotFound();
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateOptionUnauthorized(string $user): void
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
                '/attributes/id:' . $this->attribute->getKey() . '/options/id:' . $this->option->getKey(),
                $optionUpdate
            )
            ->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testDeleteOption(string $user): void
    {
        $this->$user->givePermissionTo('attributes.edit');

        $this
            ->actingAs($this->$user)
            ->deleteJson('/attributes/id:' . $this->attribute->getKey() . '/options/id:' . $this->option->getKey())
            ->assertNoContent();

        $this->assertSoftDeleted($this->option);
    }

    /**
     * @dataProvider authProvider
     */
    public function testDeleteOptionNotExisting(string $user): void
    {
        $this->$user->givePermissionTo('attributes.edit');

        $this->option->delete();

        $this
            ->actingAs($this->$user)
            ->deleteJson('/attributes/id:' . $this->attribute->getKey() . '/options/id:' . $this->option->getKey())
            ->assertNotFound();
    }

    /**
     * @dataProvider authProvider
     */
    public function testDeleteOptionNotRelatedOption(string $user): void
    {
        $this->$user->givePermissionTo('attributes.edit');

        $attribute = Attribute::factory()->create();

        $this
            ->actingAs($this->$user)
            ->deleteJson('/attributes/id:' . $attribute->getKey() . '/options/id:' . $this->option->getKey())
            ->assertNotFound();
    }

    /**
     * @dataProvider authProvider
     */
    public function testDeleteOptionUnauthorized(string $user): void
    {
        $this
            ->actingAs($this->$user)
            ->deleteJson('/attributes/id:' . $this->attribute->getKey() . '/options/id:' . $this->option->getKey())
            ->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testIncrementIndex(string $user): void
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
            ]);

        AttributeOption::factory()->create([
            'index' => 1,
            'attribute_id' => $response['data']['id'],
        ]);
        AttributeOption::factory()->create([
            'index' => 2,
            'attribute_id' => $response['data']['id'],
        ]);

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
            ]);

        $this->assertDatabaseHas('attribute_options', [
            'attribute_id' => $response['data']['id'],
            'index' => 1,
            'deleted_at' => null,
        ]);
        $this->assertDatabaseHas('attribute_options', [
            'attribute_id' => $response['data']['id'],
            'index' => 3,
            'deleted_at' => null,
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateMinMaxNumberOnUpdateOption(string $user): void
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
            ->getJson('/attributes/id:' . $attribute->getKey())
            ->assertOk()
            ->assertJsonFragment([
                'min' => 110,
                'max' => 190,
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateMinMaxNumberOnDeleteOption(string $user): void
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
            ->getJson('/attributes/id:' . $attribute->getKey())
            ->assertOk()
            ->assertJsonFragment([
                'min' => 100,
                'max' => 100,
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateMinMaxDateOnUpdateOption(string $user): void
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
            ->getJson('/attributes/id:' . $attribute->getKey())
            ->assertOk()
            ->assertJsonFragment([
                'min' => '2012-08-10',
                'max' => '2019-01-01',
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateMinMaxDateOnDeleteOption(string $user): void
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
            ->getJson('/attributes/id:' . $attribute->getKey())
            ->assertOk()
            ->assertJsonFragment([
                'min' => '2010-03-15',
                'max' => '2010-03-15',
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexAttributeOptionPrivateMetadata(string $user): void
    {
        $this->$user->givePermissionTo(['attributes.show', 'attributes.show_metadata_private']);

        unset($this->newAttribute['options']);

        /** @var Attribute $attribute */
        $attribute = Attribute::query()->create($this->newAttribute);

        /** @var Option $attrOptionOne */
        $attrOptionOne = AttributeOption::factory()->create([
            'attribute_id' => $attribute->getKey(),
            'index' => 1,
        ]);

        /** @var Option $attrOptionTwo */
        $attrOptionTwo = AttributeOption::factory()->create([
            'attribute_id' => $attribute->getKey(),
            'index' => 2,
        ]);

        $attrOptionOne->metadataPrivate()->create([
            'name' => 'qwe',
            'value' => 'asd',
            'value_type' => MetadataType::STRING,
        ]);
        $attrOptionTwo->metadataPrivate()->create([
            'name' => 'zxc',
            'value' => 'vbn',
            'value_type' => MetadataType::STRING,
        ]);

        $this
            ->actingAs($this->$user)
            ->getJson('/attributes/id:' . $attribute->getKey() . '/options?metadata_private[qwe]=asd')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['qwe' => 'asd']);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexAttributeHasOnlyItsOwnOptions(string $user): void
    {
        $this->$user->givePermissionTo(['attributes.show', 'attributes.show_metadata_private']);

        $attributeOne = Attribute::query()->create([
            'name' => 'testone',
            'slug' => 't1',
            'type' => AttributeType::SINGLE_OPTION,
            'global' => true,
            'sortable' => true,
        ]);

        $attrOptionOne = AttributeOption::factory()->create([
            'attribute_id' => $attributeOne->getKey(),
            'index' => 1,
        ]);

        $attributeTwo = Attribute::query()->create([
            'name' => 'testtwo',
            'slug' => 't2',
            'type' => AttributeType::SINGLE_OPTION,
            'global' => true,
            'sortable' => true,
        ]);

        AttributeOption::factory()->create([
            'attribute_id' => $attributeTwo->getKey(),
            'index' => 2,
        ]);

        $this
            ->actingAs($this->$user)
            ->getJson('/attributes/id:' . $attributeOne->getKey() . '/options')
            ->assertJsonCount(1, 'data')
            ->assertJson([
                'data' => [
                    [
                        'id' => $attrOptionOne->getKey(),
                        'name' => $attrOptionOne->name,
                        'value_number' => $attrOptionOne->value_number,
                        'value_date' => $attrOptionOne->value_date,
                        'attribute_id' => $attributeOne->getKey(),
                    ],
                ],
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexAttributeOptionName(string $user): void
    {
        $this->$user->givePermissionTo(['attributes.show', 'attributes.show_metadata_private']);

        unset($this->newAttribute['options']);
        /** @var Attribute $attribute */
        $attribute = Attribute::query()->create($this->newAttribute);

        AttributeOption::factory()->create([
            'attribute_id' => $attribute->getKey(),
            'index' => 1,
            'name' => 'Searched name',
        ]);
        AttributeOption::factory()->create([
            'attribute_id' => $attribute->getKey(),
            'index' => 2,
            'name' => 'Another name',
        ]);

        $this
            ->actingAs($this->$user)
            ->json('GET', '/attributes/id:' . $attribute->getKey() . '/options', ['name' => 'Searched name'])
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['name' => 'Searched name'])
            ->assertJsonMissing(['name' => 'Another name']);
    }
}
