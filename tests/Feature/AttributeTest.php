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
    private array $newAttribute;
    private array $expectedStructure;

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

        $this->expectedStructure = [
            'data' => [
                'name',
                'description',
                'type',
                'global',
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

        $response = $this
            ->actingAs($this->$user)
            ->getJson('/attributes');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data');
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
                'description' => $this->newAttribute['description'],
                'type' => $this->newAttribute['type'],
                'global' => $this->newAttribute['global'],
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
            'description' => 'Test ' . $this->attribute->description,
            'type' => AttributeType::NUMBER,
            'global' => true,
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
                'description' => $attributeUpdate['description'],
                'type' => $attributeUpdate['type'],
                'global' => $attributeUpdate['global'],
            ])
            ->assertJsonFragment([
                'value_text' => $attributeUpdate['options'][0]['value_text'],
                'value' => $attributeUpdate['options'][0]['value']
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateUnauthorized($user)
    {
        $attributeUpdate = [
            'name' => 'Test ' . $this->attribute->name,
            'description' => 'Test ' . $this->attribute->description,
            'type' => AttributeType::NUMBER,
            'global' => true,
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
    public function testDeleteUnauthorized($user)
    {
        $this
            ->actingAs($this->$user)
            ->deleteJson('/attributes/id:' . $this->attribute->getKey())
            ->assertForbidden();
    }
}
