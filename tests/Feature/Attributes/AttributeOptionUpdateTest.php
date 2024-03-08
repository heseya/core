<?php

namespace Tests\Feature\Attributes;

use Domain\ProductAttribute\Enums\AttributeType;
use Domain\ProductAttribute\Models\Attribute;
use Domain\ProductAttribute\Models\AttributeOption;
use Illuminate\Support\Carbon;

class AttributeOptionUpdateTest extends AttributeOptionTestCase
{
    /**
     * @dataProvider authProvider
     */
    public function testUpdateOption(string $user): void
    {
        $this->{$user}->givePermissionTo('attributes.edit');

        $optionUpdate = [
            'id' => $this->option->getKey(),
            'value_number' => $this->option->value_number + 1,
            'value_date' => Carbon::now()->toDateString(),
            'attribute_id' => $this->option->attribute_id,
        ];

        $this
            ->actingAs($this->{$user})
            ->json(
                'PATCH',
                '/attributes/id:' . $this->attribute->getKey() . '/options/id:' . $this->option->getKey(),
                array_merge([
                    'translations' => [
                        $this->lang => [
                            'name' => 'Test ' . $this->option->name,
                        ],
                    ],
                    'published' => [
                        $this->lang,
                    ],
                ], $optionUpdate)
            )
            ->assertOk()
            ->assertJsonFragment($optionUpdate);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateOptionWithoutId(string $user): void
    {
        $this->{$user}->givePermissionTo('attributes.edit');

        $optionUpdate = [
            'value_number' => $this->option->value_number + 1,
            'value_date' => Carbon::now()->toDateString(),
            'attribute_id' => $this->option->attribute_id,
        ];

        $this
            ->actingAs($this->{$user})
            ->json(
                'PATCH',
                '/attributes/id:' . $this->attribute->getKey() . '/options/id:' . $this->option->getKey(),
                array_merge([
                    'translations' => [
                        $this->lang => [
                            'name' => 'Test ' . $this->option->name,
                        ],
                    ],
                    'published' => [
                        $this->lang,
                    ],
                ], $optionUpdate)
            )
            ->assertCreated()
            ->assertJsonFragment($optionUpdate);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateValueNumberInvalidMax(string $user): void
    {
        $this->{$user}->givePermissionTo('attributes.edit');

        $optionUpdate = [
            'value_number' => 999999.99999,
            'value_date' => Carbon::now()->toDateString(),
            'attribute_id' => $this->option->attribute_id,
        ];

        $this
            ->actingAs($this->{$user})
            ->json(
                'PATCH',
                '/attributes/id:' . $this->attribute->getKey() . '/options/id:' . $this->option->getKey(),
                array_merge([
                    'translations' => [
                        $this->lang => [
                            'name' => 'Test ' . $this->option->name,
                        ],
                    ],
                    'published' => [
                        $this->lang,
                    ],
                ], $optionUpdate)
            )
            ->assertUnprocessable()
            ->assertJsonFragment([
                'key' => 'VALIDATION_MAX',
                'message' => 'The value number may not be greater than 999999.9999.',
            ])
            ->assertJsonFragment([
                'key' => 'VALIDATION_DECIMAL',
                'message' => 'The value number field must have 0-4 decimal places.',
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateValueNumberInvalidMin(string $user): void
    {
        $this->{$user}->givePermissionTo('attributes.edit');

        $optionUpdate = [
            'value_number' => -1,
            'value_date' => Carbon::now()->toDateString(),
            'attribute_id' => $this->option->attribute_id,
        ];

        $this
            ->actingAs($this->{$user})
            ->json(
                'PATCH',
                '/attributes/id:' . $this->attribute->getKey() . '/options/id:' . $this->option->getKey(),
                array_merge([
                    'translations' => [
                        $this->lang => [
                            'name' => 'Test ' . $this->option->name,
                        ],
                    ],
                    'published' => [
                        $this->lang,
                    ],
                ], $optionUpdate)
            )
            ->assertUnprocessable()
            ->assertJsonFragment([
                'key' => 'VALIDATION_MIN',
                'message' => 'The value number must be at least  0.',
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateOptionIncompleteData(string $user): void
    {
        $this->{$user}->givePermissionTo('attributes.edit');

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
            ->actingAs($this->{$user})
            ->json(
                'PATCH',
                '/attributes/id:' . $attribute->getKey() . '/options/id:' . $option->getKey(),
                $optionUpdate,
            )
            ->assertUnprocessable();
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateOptionNotExisting(string $user): void
    {
        $this->{$user}->givePermissionTo('attributes.edit');

        $optionUpdate = [
            'id' => $this->option->id,
            'name' => 'Test ' . $this->option->name,
            'value_number' => $this->option->value_number + 1,
            'value_date' => Carbon::now()->toDateString(),
            'attribute_id' => $this->option->attribute_id,
        ];

        $this->option->delete();

        $this
            ->actingAs($this->{$user})
            ->json(
                'PATCH',
                '/attributes/id:' . $this->attribute->getKey() . '/options/id:' . $this->option->getKey(),
                $optionUpdate,
            )
            ->assertNotFound();
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateOptionNotRelatedOption(string $user): void
    {
        $this->{$user}->givePermissionTo('attributes.edit');

        $attribute = Attribute::factory()->create();

        $optionUpdate = [
            'id' => $this->option->id,
            'name' => 'Test ' . $this->option->name,
            'value_number' => $this->option->value_number + 1,
            'value_date' => Carbon::now()->toDateString(),
            'attribute_id' => $this->option->attribute_id,
        ];

        $this
            ->actingAs($this->{$user})
            ->json(
                'PATCH',
                '/attributes/id:' . $attribute->getKey() . '/options/id:' . $this->option->getKey(),
                $optionUpdate,
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
            ->actingAs($this->{$user})
            ->patchJson(
                '/attributes/id:' . $this->attribute->getKey() . '/options/id:' . $this->option->getKey(),
                $optionUpdate,
            )
            ->assertForbidden();
    }
}
