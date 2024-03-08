<?php

namespace Tests\Feature\Attributes;

use Domain\ProductAttribute\Models\Attribute;
use Domain\ProductAttribute\Models\AttributeOption;
use Ramsey\Uuid\Uuid;

class AttributeCreateTest extends AttributeTestCase
{
    /** @var array<string, mixed> */
    private array $newAttribute;
    /** @var array<string, array<string>> */
    private array $expectedStructure;

    public function setUp(): void
    {
        parent::setUp();

        $this->newAttribute = array_merge($this->attributeData, [
            'translations' => [
                $this->lang => [
                    'name' => 'new attribute',
                    'description' => 'lorem ipsum',
                ],
            ],
        ]);
        $this->newAttribute['options'] = [
            AttributeOption::factory()->definition(),
            AttributeOption::factory()->definition(),
        ];

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
    public function testCreate(string $user): void
    {
        $this->{$user}->givePermissionTo('attributes.add');

        $this
            ->actingAs($this->{$user})
            ->postJson('/attributes', $this->newAttribute)
            ->assertCreated()
            ->assertJsonStructure($this->expectedStructure)
            ->assertJsonFragment($this->attributeData);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateWithUuid(string $user): void
    {
        $this->{$user}->givePermissionTo('attributes.add');

        $uuid = Uuid::uuid4()->toString();

        $this->actingAs($this->{$user})->postJson('/attributes', $this->newAttribute + ['id' => $uuid])
            ->assertCreated()
            ->assertJsonStructure($this->expectedStructure)
            ->assertJsonFragment([
                'name' => $this->attributeData['name'],
                'id' => $uuid,
            ]);

        $this->assertDatabaseHas('attributes', [
            'id' => $uuid,
            "name->{$this->lang}" => $this->attributeData['name'],
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateWithMetadata(string $user): void
    {
        $this->{$user}->givePermissionTo('attributes.add');

        $response = $this->actingAs($this->{$user})
            ->postJson('/attributes', $this->newAttribute + [
                    'metadata' => [
                        'attributeMeta' => 'attributeValueOne',
                    ],
                    'metadata_private' => [
                        'attributeMetaPriv' => 'attributeValueOnePriv',
                    ],
                ])
            ->assertCreated();

        /** @var Attribute $createdAttribute */
        $createdAttribute = Attribute::whereId($response->json('data.id'))->first();

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
    public function testCreateIncompleteData(string $user): void
    {
        $this->{$user}->givePermissionTo('attributes.add');

        unset($this->newAttribute['translations']);

        $this
            ->actingAs($this->{$user})
            ->postJson('/attributes', $this->newAttribute)
            ->assertUnprocessable();
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateUnauthorized(string $user): void
    {
        $this
            ->actingAs($this->{$user})
            ->postJson('/attributes', $this->newAttribute)
            ->assertForbidden();
    }
}
