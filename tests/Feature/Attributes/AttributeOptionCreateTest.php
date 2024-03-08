<?php

namespace Tests\Feature\Attributes;

use Domain\ProductAttribute\Enums\AttributeType;
use Domain\ProductAttribute\Models\Attribute;
use Ramsey\Uuid\Uuid;

class AttributeOptionCreateTest extends AttributeOptionTestCase
{
    /**
     * @dataProvider authProvider
     */
    public function testAddOption(string $user): void
    {
        $this->{$user}->givePermissionTo('attributes.edit');

        $response = $this
            ->actingAs($this->{$user})
            ->postJson('/attributes/id:' . $this->attribute->getKey() . '/options', $this->newOption);
        $response
            ->assertCreated()
            ->assertJsonFragment($this->optionData);

        $this->assertDatabaseHas('attribute_options', [
            "name->{$this->lang}" => $this->optionData['name'],
            'value_number' => $this->optionData['value_number'],
            'value_date' => $this->optionData['value_date'],
            'order' => 1,
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testAddOptionWithUuid(string $user): void
    {
        $this->{$user}->givePermissionTo('attributes.edit');

        $uuid = Uuid::uuid4()->toString();

        $this
            ->actingAs($this->{$user})
            ->postJson('/attributes/id:' . $this->attribute->getKey() . '/options', $this->newOption + [
                    'id' => $uuid,
                ])
            ->assertCreated()
            ->assertJsonFragment($this->optionData);

        $this->assertDatabaseHas('attribute_options', [
            'id' => $uuid,
            "name->{$this->lang}" => $this->optionData['name'],
            'value_number' => $this->optionData['value_number'],
            'value_date' => $this->optionData['value_date'],
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testAddOptionWithMetadata(string $user): void
    {
        $this->{$user}->givePermissionTo('attributes.edit');

        $response = $this->actingAs($this->{$user})
            ->postJson('/attributes/id:' . $this->attribute->getKey() . '/options', $this->newOption + [
                    'metadata' => [
                        'optionMeta' => 'testValue',
                    ],
                ])
            ->assertCreated()
            ->assertJsonFragment($this->optionData);

        $this->assertDatabaseHas('attribute_options', [
            "name->{$this->lang}" => $this->optionData['name'],
            'value_number' => $this->optionData['value_number'],
            'value_date' => $this->optionData['value_date'],
        ])
            ->assertDatabaseCount('metadata', 1)
            ->assertDatabaseHas('metadata', [
                'name' => 'optionMeta',
                'value' => 'testValue',
                'model_id' => $response->json('data.id'),
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testAddOptionNumberWithoutName(string $user): void
    {
        $this->{$user}->givePermissionTo('attributes.edit');

        $attribute = Attribute::factory([
            'type' => AttributeType::NUMBER,
        ])->create();
        unset($this->newOption['translations']);
        unset($this->newOption['published']);
        unset($this->optionData['name']);

        $this->newOption['value_number'] = '12';
        $this->optionData['value_number'] = 12;

        $this
            ->actingAs($this->{$user})
            ->postJson('/attributes/id:' . $attribute->getKey() . '/options', $this->newOption)
            ->assertCreated()
            ->assertJsonFragment($this->optionData);

        $this->assertDatabaseHas('attribute_options', $this->newOption);
    }

    /**
     * @dataProvider authProvider
     */
    public function testAddOptionNumberWithoutNumber(string $user): void
    {
        $this->{$user}->givePermissionTo('attributes.edit');

        $attribute = Attribute::factory([
            'type' => AttributeType::NUMBER,
        ])->create();
        unset($this->newOption['translations']);
        unset($this->newOption['published']);
        unset($this->optionData['name']);

        $this
            ->actingAs($this->{$user})
            ->postJson('/attributes/id:' . $attribute->getKey() . '/options', $this->newOption)
            ->assertUnprocessable()
            ->assertJsonFragment([
                'message' => 'The value number field is required.'
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testAddOptionIncompleteData(string $user): void
    {
        $this->{$user}->givePermissionTo('attributes.edit');

        $attribute = Attribute::factory([
            'type' => AttributeType::SINGLE_OPTION,
        ])->create();
        unset($this->newOption['translations']);

        $this
            ->actingAs($this->{$user})
            ->postJson('/attributes/id:' . $attribute->getKey() . '/options', $this->newOption)
            ->assertUnprocessable();
    }

    /**
     * @dataProvider authProvider
     */
    public function testAddOptionToDeletedAttribute(string $user): void
    {
        $this->{$user}->givePermissionTo('attributes.edit');

        Attribute::destroy($this->attribute->getKey());

        $this
            ->actingAs($this->{$user})
            ->postJson('/attributes/id:' . $this->attribute->getKey() . '/options', $this->newOption)
            ->assertNotFound();
    }

    /**
     * @dataProvider authProvider
     */
    public function testAddOptionUnauthorized(string $user): void
    {
        $this
            ->actingAs($this->{$user})
            ->postJson('/attributes/id:' . $this->attribute->getKey() . '/options', $this->newOption)
            ->assertForbidden();
    }
}
