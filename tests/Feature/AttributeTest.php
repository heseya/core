<?php

namespace Tests\Feature;

use App\Enums\AttributeType;
use App\Models\Attribute;
use App\Models\AttributeOption;
use App\Models\ProductSet;
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
            'attribute_id' => $this->attribute->getKey()
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
                'type',
                'global',
                'sortable',
                'options'
            ]
        ];
    }

    public function testIndexUnauthorized()
    {
        $response = $this->getJson('/attributes');
        $response->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndex($user)
    {
        $this->$user->givePermissionTo('attributes.show');

        $this
            ->actingAs($this->$user)
            ->getJson('/attributes')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment([
                'name' => $this->attribute->name,
                'slug' => $this->attribute->slug,
                'description' => $this->attribute->description,
                'type' => $this->attribute->type,
                'global' => $this->attribute->global,
                'sortable' => $this->attribute->sortable,
            ])
            ->assertJsonFragment([
                'value_text' => $this->option->value_text,
                'value' => $this->option->value
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreate($user)
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
            ->assertJsonFragment([
                'value_text' => $this->newAttribute['options'][0]['value_text'],
                'value' => $this->newAttribute['options'][0]['value']
            ])
            ->assertJsonFragment([
                'value_text' => $this->newAttribute['options'][1]['value_text'],
                'value' => $this->newAttribute['options'][1]['value']
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateIncompleteData($user)
    {
        $this->$user->givePermissionTo('attributes.add');

        unset($this->newAttribute['name']);

        $this
            ->actingAs($this->$user)
            ->postJson('/attributes', $this->newAttribute)
            ->assertStatus(422);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateUnauthorized($user)
    {
        $this
            ->actingAs($this->$user)
            ->postJson('/attributes', $this->newAttribute)
            ->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdate($user)
    {
        $this->$user->givePermissionTo('attributes.edit');

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
                    'value_text' => 'Test ' . $this->option->value_text,
                    'value' => $this->option->value,
                ],
            ]
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
            ->assertJsonFragment([
                'value_text' => $attributeUpdate['options'][0]['value_text'],
                'value' => $attributeUpdate['options'][0]['value']
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateIncompleteData($user)
    {
        $this->$user->givePermissionTo('attributes.edit');

        $attributeUpdate = [
            'name' => 'Test update attribute name',
        ];

        $this
            ->actingAs($this->$user)
            ->patchJson('/attributes/id:' . $this->attribute->getKey(), $attributeUpdate)
            ->assertStatus(422);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateNotExistingAttribute($user)
    {
        $this->$user->givePermissionTo('attributes.edit');

        Attribute::destroy($this->attribute->getKey());

        $attributeUpdate = [
            'name' => 'Test update attribute name',
        ];

        $this
            ->actingAs($this->$user)
            ->patchJson('/attributes/id:' . $this->attribute->getKey(), $attributeUpdate)
            ->assertStatus(404);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateUnauthorized($user)
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
                    'value_text' => 'Test ' . $this->option->value_text,
                    'value' => $this->option->value,
                ],
            ]
        ];

        $this
            ->actingAs($this->$user)
            ->patchJson('/attributes/id:' . $this->attribute->getKey(), $attributeUpdate)
            ->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testDelete($user)
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
    public function testDeleteNotExistingAttribute($user)
    {
        $this->$user->givePermissionTo('attributes.remove');

        Attribute::destroy($this->attribute->getKey());

        $this
            ->actingAs($this->$user)
            ->deleteJson('/attributes/id:' . $this->attribute->getKey())
            ->assertStatus(404);
    }

    /**
     * @dataProvider authProvider
     */
    public function testDeleteUnauthorized($user)
    {
        $this
            ->actingAs($this->$user)
            ->deleteJson('/attributes/id:' . $this->attribute->getKey())
            ->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testAddOption($user)
    {
        $this->$user->givePermissionTo('attributes.edit');

        $this
            ->actingAs($this->$user)
            ->postJson('/attributes/id:' . $this->attribute->getKey() . '/options', $this->newOption)
            ->assertCreated()
            ->assertJsonFragment([
                'value_text' => $this->newOption['value_text'],
                'value' => $this->newOption['value'],
            ]);

        $this->assertDatabaseHas('attribute_options', $this->newOption);
    }

    /**
     * @dataProvider authProvider
     */
    public function testAddOptionIncompleteData($user)
    {
        $this->$user->givePermissionTo('attributes.edit');

        unset($this->newOption['value_text']);

        $this
            ->actingAs($this->$user)
            ->postJson('/attributes/id:' . $this->attribute->getKey() . '/options', $this->newOption)
            ->assertStatus(422);
    }

    /**
     * @dataProvider authProvider
     */
    public function testAddOptionToDeletedAttribute($user)
    {
        $this->$user->givePermissionTo('attributes.edit');

        Attribute::destroy($this->attribute->getKey());

        $this
            ->actingAs($this->$user)
            ->postJson('/attributes/id:' . $this->attribute->getKey() . '/options', $this->newOption)
            ->assertStatus(404);
    }

    /**
     * @dataProvider authProvider
     */
    public function testAddOptionUnauthorized($user)
    {
        $this
            ->actingAs($this->$user)
            ->postJson('/attributes/id:' . $this->attribute->getKey() . '/options', $this->newOption)
            ->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testFilterGlobal($user): void
    {
        Attribute::query()->delete();

        Attribute::factory()->count(3)->create(['global' => 1]);
        Attribute::factory()->create(['global' => 0]);

        $this->actingAs($this->$user)->getJson('/filters')
            ->assertJsonCount(3, 'data');
    }

    /**
     * @dataProvider authProvider
     */
    public function testFilterWithSetsIds($user): void
    {
        Attribute::query()->delete();

        $optionOne = AttributeOption::create([
            'value_text' => 'test',
            'value' => 1,
        ]);
        $optionTwo = AttributeOption::create([
            'value_text' => 'test2',
            'value' => 99,
        ]);

        $singleOptionAttribute = Attribute::factory()->create([
            'global' => 0,
            'type' => 'single-option',
        ]);
        $singleOptionAttribute->options()->saveMany([
           $optionOne,
           $optionTwo,
        ]);

        $firstProductSet = ProductSet::factory()->create();
        $firstProductSet->attributes()->attach([
            Attribute::factory()->create([
                'global' => 0,
                'type' => 'number',
            ])->getKey(),
            Attribute::factory()->create([
                'global' => 0,
                'type' => 'date',
            ])->getKey(),
        ]);

        $secondProductSet = ProductSet::factory()->create();
        $secondProductSet->attributes()->attach([
            Attribute::factory()->create([
                'global' => 0,
                'type' => 'number',
            ])->getKey(),
            $singleOptionAttribute->getKey(),
            Attribute::factory()->create([
                'global' => 0,
                'type' => 'date',
            ])->getKey(),
        ]);

        ProductSet::factory()->create()->attributes()->attach([
            Attribute::factory()->create([
                'global' => 0,
                'type' => 'number',
            ])->getKey(),
            Attribute::factory()->create([
                'global' => 1,
                'type' => 'date',
            ])->getKey(),
            Attribute::factory()->create([
                'global' => 0,
                'type' => 'single-option',
            ])->getKey(),
        ]);

        $response = $this->actingAs($this->$user)
            ->getJson('/filters?sets[]=' . $firstProductSet->getKey() . '&sets[]=' . $secondProductSet->getKey());

        $response
            ->assertJsonFragment([
                'value_text' => 'test',
                'value' => 1,
            ])
            ->assertJsonFragment([
                'value_text' => 'test2',
                'value' => 99,
            ])
            ->assertJsonCount(6, 'data');
    }
}
