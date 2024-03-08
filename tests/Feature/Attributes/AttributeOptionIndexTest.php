<?php

namespace Tests\Feature\Attributes;

use App\Models\Option;
use Domain\Metadata\Enums\MetadataType;
use Domain\ProductAttribute\Enums\AttributeType;
use Domain\ProductAttribute\Models\Attribute;
use Domain\ProductAttribute\Models\AttributeOption;

class AttributeOptionIndexTest extends AttributeOptionTestCase
{
    /** @var array<string, mixed> */
    private array $attributeData;
    /** @var array<string, mixed> */
    private array $newAttribute;

    public function setUp(): void
    {
        parent::setUp();

        $this->attributeData = [
            'name' => 'new attribute',
            'slug' => 'new-attribute',
            'description' => 'lorem ipsum',
            'type' => AttributeType::SINGLE_OPTION,
            'global' => false,
            'sortable' => true,
            'published' => [$this->lang],
        ];
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
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexOptions(string $user): void
    {
        $this->{$user}->givePermissionTo('attributes.show');

        $this
            ->actingAs($this->{$user})
            ->getJson("/attributes/id:{$this->attribute->getKey()}/options")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment([
                'id' => $this->option->getKey(),
                'name' => $this->option->name,
            ]);
    }

    public function testIndexOptionsUnauthorized(): void
    {
        $this
            ->getJson("/attributes/id:{$this->attribute->getKey()}/options")
            ->assertForbidden()
            ->assertJsonFragment(['message' => 'This action is unauthorized.']);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexOptionsWithPagination(string $user): void
    {
        $this->{$user}->givePermissionTo('attributes.show');

        AttributeOption::factory()
            ->count(20)
            ->sequence(fn ($sequence) => ['index' => $sequence->index])
            ->create([
                'attribute_id' => $this->attribute->getKey(),
            ]);

        $this
            ->actingAs($this->{$user})
            ->json('GET', "/attributes/id:{$this->attribute->getKey()}/options", ['limit' => 10])
            ->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonFragment([
                'per_page' => 10,
                'to' => 10,
                'total' => 21,
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexOptionsWrongId(string $user): void
    {
        $this->{$user}->givePermissionTo('attributes.show');

        $this
            ->actingAs($this->{$user})
            ->getJson('/attributes/id:its-not-uuid/options')
            ->assertNotFound();

        $this
            ->actingAs($this->{$user})
            ->getJson("/attributes/id:{$this->attribute->getKey()}{$this->attribute->getKey()}/options")
            ->assertNotFound();
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexOptionsMetadata(string $user): void
    {
        $this->{$user}->givePermissionTo('attributes.show');

        $option = AttributeOption::create(
            $this->optionData +
            [
                'index' => 1,
                'attribute_id' => $this->attribute->getKey(),
            ],
        );
        $option->metadata()->create([
            'name' => 'Dystrybucja',
            'value' => 'Polska',
            'value_type' => MetadataType::STRING,
            'public' => true,
        ]);

        $this
            ->actingAs($this->{$user})
            ->getJson("/attributes/id:{$this->attribute->getKey()}/options?metadata[Dystrybucja]=Polska")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(
                array_merge($this->optionData, ['Dystrybucja' => 'Polska']),
            );
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexOptionsMetadataNotFound(string $user): void
    {
        $this->{$user}->givePermissionTo('attributes.show');

        $option = AttributeOption::create(
            $this->newOption +
            [
                'index' => 1,
                'attribute_id' => $this->attribute->getKey(),
            ],
        );
        $option->metadata()->create([
            'name' => 'Dystrybucja',
            'value' => 'Francja',
            'value_type' => MetadataType::STRING,
            'public' => true,
        ]);

        $this
            ->actingAs($this->{$user})
            ->getJson("/attributes/id:{$this->attribute->getKey()}/options?metadata[Dystrybucja]=Polska")
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexOptionsMetadataPrivate(string $user): void
    {
        $this->{$user}->givePermissionTo(['attributes.show', 'attributes.show_metadata_private']);

        $option = AttributeOption::create(
            $this->optionData +
            [
                'index' => 1,
                'attribute_id' => $this->attribute->getKey(),
            ],
        );
        $option->metadata()->create([
            'name' => 'Dystrybucja',
            'value' => 'Polska',
            'value_type' => MetadataType::STRING,
            'public' => true,
        ]);

        $this
            ->actingAs($this->{$user})
            ->getJson("/attributes/id:{$this->attribute->getKey()}/options?metadata[Dystrybucja]=Polska")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(
                array_merge($this->optionData, ['Dystrybucja' => 'Polska']),
            );
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexOptionsMetadataPrivateNotFound(string $user): void
    {
        $this->{$user}->givePermissionTo(['attributes.show', 'attributes.show_metadata_private']);

        $option = AttributeOption::create(
            $this->optionData +
            [
                'index' => 1,
                'attribute_id' => $this->attribute->getKey(),
            ],
        );
        $option->metadata()->create([
            'name' => 'Dystrybucja',
            'value' => 'Francja',
            'value_type' => MetadataType::STRING,
            'public' => true,
        ]);

        $this
            ->actingAs($this->{$user})
            ->getJson("/attributes/id:{$this->attribute->getKey()}/options?metadata[Dystrybucja]=Polska")
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexAttributeOptionPrivateMetadata(string $user): void
    {
        $this->{$user}->givePermissionTo(['attributes.show', 'attributes.show_metadata_private']);

        /** @var Attribute $attribute */
        $attribute = Attribute::query()->create($this->attributeData);

        /** @var Option $attrOptionOne */
        $attrOptionOne = AttributeOption::factory()->create([
            'attribute_id' => $attribute->getKey(),
            'index' => 1,
        ]);

        /** @var Option $attrOptionTwo */
        $attrOptionTwo = AttributeOption::factory()->create([
            'attribute_id' => $attribute->getKey(),
            'index' => 2,
        ]);

        $attrOptionOne->metadataPrivate()->create([
            'name' => 'qwe',
            'value' => 'asd',
            'value_type' => MetadataType::STRING,
        ]);
        $attrOptionTwo->metadataPrivate()->create([
            'name' => 'zxc',
            'value' => 'vbn',
            'value_type' => MetadataType::STRING,
        ]);

        $this
            ->actingAs($this->{$user})
            ->getJson('/attributes/id:' . $attribute->getKey() . '/options?metadata_private[qwe]=asd')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['qwe' => 'asd']);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexAttributeHasOnlyItsOwnOptions(string $user): void
    {
        $this->{$user}->givePermissionTo(['attributes.show', 'attributes.show_metadata_private']);

        $attributeOne = Attribute::query()->create([
            'name' => 'testone',
            'slug' => 't1',
            'type' => AttributeType::SINGLE_OPTION,
            'global' => true,
            'sortable' => true,
        ]);

        /** @var AttributeOption $attrOptionOne */
        $attrOptionOne = AttributeOption::factory()->create([
            'attribute_id' => $attributeOne->getKey(),
            'index' => 1,
        ]);

        $attributeTwo = Attribute::query()->create([
            'name' => 'testtwo',
            'slug' => 't2',
            'type' => AttributeType::SINGLE_OPTION,
            'global' => true,
            'sortable' => true,
        ]);

        AttributeOption::factory()->create([
            'attribute_id' => $attributeTwo->getKey(),
            'index' => 2,
        ]);

        $this
            ->actingAs($this->{$user})
            ->getJson('/attributes/id:' . $attributeOne->getKey() . '/options')
            ->assertJsonCount(1, 'data')
            ->assertJson([
                'data' => [
                    [
                        'id' => $attrOptionOne->getKey(),
                        'name' => $attrOptionOne->name,
                        'value_number' => $attrOptionOne->value_number,
                        'value_date' => $attrOptionOne->value_date,
                        'attribute_id' => $attributeOne->getKey(),
                    ],
                ],
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexAttributeOptionName(string $user): void
    {
        $this->{$user}->givePermissionTo(['attributes.show', 'attributes.show_metadata_private']);

        unset($this->newAttribute['options']);
        /** @var Attribute $attribute */
        $attribute = Attribute::query()->create($this->attributeData);

        AttributeOption::factory()->create([
            'attribute_id' => $attribute->getKey(),
            'index' => 1,
            'name' => 'Searched name',
        ]);
        AttributeOption::factory()->create([
            'attribute_id' => $attribute->getKey(),
            'index' => 2,
            'name' => 'Another name',
        ]);

        $this
            ->actingAs($this->{$user})
            ->json('GET', '/attributes/id:' . $attribute->getKey() . '/options', ['name' => 'Searched name'])
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['name' => 'Searched name'])
            ->assertJsonMissing(['name' => 'Another name']);
    }

    /**
     * @return array<string, array<int, string|AttributeType>>
     */
    public static function optionsSortProvider(): array
    {
        return [
            'as user text' => ['user', AttributeType::SINGLE_OPTION],
            'as user number' => ['user', AttributeType::NUMBER],
            'as user date' => ['user', AttributeType::DATE],
            'as app text' => ['application', AttributeType::SINGLE_OPTION],
            'as app number' => ['application', AttributeType::NUMBER],
            'as app date' => ['application', AttributeType::DATE],
        ];
    }

    /**
     * @dataProvider optionsSortProvider
     */
    public function testIndexSortDefault(string $user, AttributeType $type): void
    {
        $this->{$user}->givePermissionTo('attributes.show');

        $attribute = Attribute::factory()->create([
            'type' => $type,
        ]);
        $option1 = AttributeOption::factory()->create([
            'name' => 'Bname',
            'attribute_id' => $attribute->getKey(),
            'index' => 0,
            'order' => 1,
            'value_number' => 12,
            'value_date' => '2023-12-29',
        ]);

        $option2 = AttributeOption::factory()->create([
            'name' => 'Aname',
            'attribute_id' => $attribute->getKey(),
            'index' => 0,
            'order' => 0,
            'value_number' => 10,
            'value_date' => '2023-12-28',
        ]);

        $this
            ->actingAs($this->{$user})
            ->json('GET', "/attributes/id:{$attribute->getKey()}/options")
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $option2->getKey())
            ->assertJsonPath('data.1.id', $option1->getKey());
    }

    /**
     * @dataProvider optionsSortProvider
     */
    public function testIndexSort(string $user, AttributeType $type): void
    {
        $this->{$user}->givePermissionTo('attributes.show');

        $attribute = Attribute::factory()->create([
            'type' => $type,
        ]);
        $option1 = AttributeOption::factory()->create([
            'name' => 'Bname',
            'attribute_id' => $attribute->getKey(),
            'index' => 0,
            'order' => 1,
            'value_number' => 12,
            'value_date' => '2023-12-29',
        ]);

        $option2 = AttributeOption::factory()->create([
            'name' => 'Aname',
            'attribute_id' => $attribute->getKey(),
            'index' => 0,
            'order' => 0,
            'value_number' => 10,
            'value_date' => '2023-12-28',
        ]);

        $this
            ->actingAs($this->{$user})
            ->json('GET', "/attributes/id:{$attribute->getKey()}/options", ['sort' => 'asc'])
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $option2->getKey())
            ->assertJsonPath('data.1.id', $option1->getKey());

        $this
            ->actingAs($this->{$user})
            ->json('GET', "/attributes/id:{$attribute->getKey()}/options", ['sort' => 'desc'])
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $option1->getKey())
            ->assertJsonPath('data.1.id', $option2->getKey());
    }
}
