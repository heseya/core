<?php

namespace Tests\Feature;

use App\Enums\AttributeType;
use App\Models\Attribute;
use App\Models\AttributeOption;
use Illuminate\Support\Str;
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
                'index' => $this->option->index,
                'name' => $this->option->name,
                'value_number' => $this->option->value_number,
                'value_date' => $this->option->value_date
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
            ->assertJsonFragment(['index' => 1] + $this->newAttribute['options'][0])
            ->assertJsonFragment(['index' => 2] + $this->newAttribute['options'][1]);
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
                    'name' => 'Test ' . $this->option->name,
                    'value_number' => $this->option->value_number,
                    'value_date' => $this->option->value_date,
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
            ->assertJsonFragment($attributeUpdate['options'][0]);
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
                    'name' => 'Test ' . $this->option->name,
                    'value_number' => $this->option->value_number,
                    'value_date' => $this->option->value_date,
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
            ->assertJsonFragment($this->newOption);

        $this->assertDatabaseHas('attribute_options', $this->newOption);
    }

    /**
     * @dataProvider authProvider
     */
    public function testAddOptionIncompleteData($user)
    {
        $this->$user->givePermissionTo('attributes.edit');

        unset($this->newOption['name']);

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
    public function testIncrementIndex($user)
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
}
