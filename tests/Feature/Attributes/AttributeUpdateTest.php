<?php

namespace Tests\Feature\Attributes;

use App\Enums\ValidationError;
use Domain\ProductAttribute\Enums\AttributeType;
use Domain\ProductAttribute\Models\Attribute;

class AttributeUpdateTest extends AttributeTestCase
{
    /** @var array<string, array<string>> */
    private array $expectedStructure;

    public function setUp(): void
    {
        parent::setUp();

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
    /**
     * @dataProvider authProvider
     */
    public function testUpdate(string $user): void
    {
        $this->{$user}->givePermissionTo('attributes.edit');

        $name = 'Test ' . $this->attribute->name;
        $attributeUpdate = [
            'translations' => [
                $this->lang => [
                    'name' => $name,
                ],
            ],
            'published' => [
                $this->lang,
            ],
        ];

        $this
            ->actingAs($this->{$user})
            ->patchJson('/attributes/id:' . $this->attribute->getKey(), $attributeUpdate)
            ->assertOk()
            ->assertJsonStructure($this->expectedStructure)
            ->assertJsonFragment(['name' => $name]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateSameSlug(string $user): void
    {
        $this->{$user}->givePermissionTo('attributes.edit');

        $name = 'Test ' . $this->attribute->name;
        $attributeUpdate = [
            'translations' => [
                $this->lang => [
                    'name' => $name,
                ],
            ],
            'slug' => $this->attribute->slug,
            'published' => [
                $this->lang,
            ],
        ];

        $this
            ->actingAs($this->{$user})
            ->patchJson('/attributes/id:' . $this->attribute->getKey(), $attributeUpdate)
            ->assertOk()
            ->assertJsonStructure($this->expectedStructure)
            ->assertJsonFragment([
                'name' => $name,
                'slug' => $this->attribute->slug,
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateChangeType(string $user): void
    {
        $this->{$user}->givePermissionTo('attributes.edit');

        $attribute = Attribute::factory()->create([
            'type' => AttributeType::SINGLE_OPTION,
        ]);

        $this
            ->actingAs($this->{$user})
            ->patchJson('/attributes/id:' . $attribute->getKey(), [
                'type' => AttributeType::DATE,
            ])
            ->assertUnprocessable()
            ->assertJsonFragment([
                'key' => ValidationError::PROHIBITED->value,
                'message' => 'The type field is prohibited.',
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateSameType(string $user): void
    {
        $this->{$user}->givePermissionTo('attributes.edit');

        $attribute = Attribute::factory()->create([
            'type' => AttributeType::SINGLE_OPTION,
        ]);

        $this
            ->actingAs($this->{$user})
            ->patchJson('/attributes/id:' . $attribute->getKey(), [
                'type' => AttributeType::SINGLE_OPTION,
            ])
            ->assertOk();
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateNotExistingAttribute(string $user): void
    {
        $this->{$user}->givePermissionTo('attributes.edit');

        Attribute::destroy($this->attribute->getKey());

        $attributeUpdate = [
            'name' => 'Test update attribute name',
        ];

        $this
            ->actingAs($this->{$user})
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
        ];

        $this
            ->actingAs($this->{$user})
            ->patchJson('/attributes/id:' . $this->attribute->getKey(), $attributeUpdate)
            ->assertForbidden();
    }
}
