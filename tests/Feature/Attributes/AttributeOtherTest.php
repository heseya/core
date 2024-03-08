<?php

namespace Tests\Feature\Attributes;

use Domain\ProductAttribute\Enums\AttributeType;
use Domain\ProductAttribute\Models\Attribute;
use Domain\ProductAttribute\Models\AttributeOption;
use Tests\TestCase;

class AttributeOtherTest extends TestCase
{
    /**
     * @dataProvider authProvider
     */
    public function testIncrementIndex(string $user): void
    {
        $this->{$user}->givePermissionTo(['attributes.show', 'attributes.edit', 'attributes.add']);

        $attributeData = [
            'name' => 'new attribute',
            'slug' => 'new-attribute',
            'description' => 'lorem ipsum',
            'type' => AttributeType::SINGLE_OPTION,
            'global' => false,
            'sortable' => true,
            'published' => [$this->lang],
        ];

        $newAttribute = array_merge($attributeData, [
            'translations' => [
                $this->lang => [
                    'name' => 'new attribute',
                    'description' => 'lorem ipsum',
                ],
            ],
            'options' => [
                AttributeOption::factory()->definition(),
                AttributeOption::factory()->definition(),
            ]
        ]);

        $optionData = [
            'value_number' => null,
            'value_date' => '2023-08-09'
        ];
        $newOption = array_merge($optionData, [
            'translations' => [
                $this->lang => [
                    'name' => 'new option',
                ],
            ],
            'published' => [
                $this->lang,
            ],
        ]);
        $optionData['name'] = 'new option';

        $expectedStructure = [
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

        $response = $this
            ->actingAs($this->{$user})
            ->postJson('/attributes', $newAttribute)
            ->assertCreated()
            ->assertJsonStructure($expectedStructure)
            ->assertJsonFragment($attributeData);

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
            ->actingAs($this->{$user})
            ->postJson('/attributes/id:' . $response['data']['id'] . '/options', $newOption)
            ->assertCreated()
            ->assertJsonFragment(['index' => 3] + $optionData);

        $this
            ->actingAs($this->{$user})
            ->getJson('/attributes/id:' . $response['data']['id'])
            ->assertOk()
            ->assertJsonFragment($attributeData);

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
        $this->{$user}->givePermissionTo('attributes.show');

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
            ->actingAs($this->{$user})
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
        $this->{$user}->givePermissionTo('attributes.show');

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
            ->actingAs($this->{$user})
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
        $this->{$user}->givePermissionTo('attributes.show');

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
            ->actingAs($this->{$user})
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
        $this->{$user}->givePermissionTo('attributes.show');

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
            ->actingAs($this->{$user})
            ->getJson('/attributes/id:' . $attribute->getKey())
            ->assertOk()
            ->assertJsonFragment([
                'min' => '2010-03-15',
                'max' => '2010-03-15',
            ]);
    }
}
