<?php

namespace Tests\Feature\Attributes;

use Domain\ProductAttribute\Enums\AttributeType;
use Domain\ProductAttribute\Models\Attribute;
use Domain\ProductAttribute\Models\AttributeOption;

class AttributeShowTest extends AttributeTestCase
{
    /**
     * @dataProvider authProvider
     */
    public function testShow(string $user): void
    {
        $this->{$user}->givePermissionTo('attributes.show');

        $this
            ->actingAs($this->{$user})
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
        $this->{$user}->givePermissionTo('attributes.show');

        $this
            ->actingAs($this->{$user})
            ->getJson('/attributes/id:its-not-uuid')
            ->assertNotFound();

        $this
            ->actingAs($this->{$user})
            ->getJson('/attributes/id:' . $this->attribute->getKey() . $this->attribute->getKey())
            ->assertNotFound();
    }

    /**
     * @dataProvider authProvider
     */
    public function testShowMinMaxNumber(string $user): void
    {
        $this->{$user}->givePermissionTo('attributes.show');

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
            ->actingAs($this->{$user})
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

        // checking rest of min/max fields in attribute
        $this->assertDatabaseHas('attributes', [
            'id' => $attribute->getKey(),
            'min_date' => null,
            'max_date' => null,
        ]);
        // making sure to other attributes was not updated by this one
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
        $this->{$user}->givePermissionTo('attributes.show');

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
            ->actingAs($this->{$user})
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

        // checking rest of min/max fields in attribute
        $this->assertDatabaseHas('attributes', [
            'id' => $attribute->getKey(),
            'min_number' => null,
            'max_number' => null,
        ]);
        // making sure to other attributes was not updated by this one
        $this->assertDatabaseMissing('attributes', [
            'id' => $this->attribute->getKey(),
            'min_date' => $option2->value_date,
            'max_date' => $option1->value_date,
        ]);
    }
}
