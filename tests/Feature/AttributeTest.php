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

    public function setUp(): void
    {
        parent::setUp();

        $this->attribute = Attribute::factory()->create();

        $this->option = AttributeOption::factory()->create([
            'attribute_id' => $this->attribute->getKey()
        ]);

        $this->attribute->options = [$this->option];

        $this->newAttribute = Attribute::factory()->definition();
        $this->newAttribute['options'] = [
            AttributeOption::factory()->definition(),
            AttributeOption::factory()->definition(),
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

        $response = $this
            ->actingAs($this->$user)
            ->postJson('/attributes', $this->newAttribute)
            ->assertCreated()
            ->assertJsonFragment([
                'name' => $this->newAttribute['name'],
                'description' => $this->newAttribute['description'],
                'type' => Str::lower(AttributeType::getKey($this->newAttribute['type'])),
                'searchable' => $this->newAttribute['searchable'],
            ]);

        $this->assertIsArray($response['data']['options']);
        $this->assertIsArray($response['data']['options'][0]);
        $this->assertArrayHasKey('value_text', $response['data']['options'][0]);
        $this->assertArrayHasKey('value', $response['data']['options'][0]);
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

        $attribute = Attribute::factory()->create();

        $option = AttributeOption::factory()->create([
            'attribute_id' => $attribute->getKey()
        ]);

        $attributeUpdate = [
            'name' => 'Test update attribute name',
            'description' => 'Test update attribute decription',
            'type' => AttributeType::NUMBER,
            'searchable' => true,
            'options' => [
                [
                    'value_text' => 'Test ' . $option->value_text,
                    'value' => $option->value,
                ],
            ]
        ];

        $response = $this
            ->actingAs($this->$user)
            ->patchJson('/attributes/id:' . $attribute->getKey(), $attributeUpdate)
            ->assertOk();

        $this->assertIsArray($response['data']['options']);
        $this->assertIsArray($response['data']['options'][0]);
        $this->assertArrayHasKey('value_text', $response['data']['options'][0]);
        $this->assertArrayHasKey('value', $response['data']['options'][0]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateUnauthorized($user)
    {
        $attribute = Attribute::factory()->create();

        $attributeUpdate = [
            'name' => 'Test update attribute name',
            'description' => 'Test update attribute decription',
            'type' => AttributeType::NUMBER,
            'searchable' => true,
        ];

        $this
            ->actingAs($this->$user)
            ->patchJson('/attributes/id:' . $attribute->getKey(), $attributeUpdate)
            ->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testDelete($user)
    {
        $this->$user->givePermissionTo('attributes.remove');

        $attribute = Attribute::factory()->create();

        $this
            ->actingAs($this->$user)
            ->deleteJson('/attributes/id:' . $attribute->getKey())
            ->assertNoContent();

        $this->assertDatabaseMissing('attributes', [
            'id' => $attribute->getKey(),
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testDeleteUnauthorized($user)
    {
        $attribute = Attribute::factory()->create();

        $this
            ->actingAs($this->$user)
            ->deleteJson('/attributes/id:' . $attribute->getKey())
            ->assertForbidden();
    }
}
