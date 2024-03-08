<?php

namespace Tests\Feature\Attributes;

use Domain\Metadata\Enums\MetadataType;
use Domain\ProductAttribute\Models\Attribute;

class AttributeIndexTest extends AttributeTestCase
{
    public function testIndexUnauthorized(): void
    {
        $response = $this->getJson('/attributes');
        $response->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndex(string $user): void
    {
        $this->{$user}->givePermissionTo('attributes.show');

        $this->attributeData['global'] = !$this->attribute->global;
        Attribute::query()->create($this->attributeData);

        $this
            ->actingAs($this->{$user})
            ->getJson('/attributes')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment([
                'name' => $this->attribute->name,
                'slug' => $this->attribute->slug,
                'description' => $this->attribute->description,
                'type' => $this->attribute->type,
                'global' => $this->attribute->global,
                'sortable' => $this->attribute->sortable,
                'metadata' => [],
            ])
            ->assertJsonFragment($this->attributeData);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexByIds(string $user): void
    {
        $this->{$user}->givePermissionTo('attributes.show');

        $this->attributeData['global'] = !$this->attribute->global;
        Attribute::query()->create($this->attributeData);

        $this
            ->actingAs($this->{$user})
            ->json('GET', '/attributes', [
                'ids' => [
                    $this->attribute->getKey(),
                ],
            ])
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment([
                'name' => $this->attribute->name,
                'slug' => $this->attribute->slug,
                'description' => $this->attribute->description,
                'type' => $this->attribute->type,
                'global' => $this->attribute->global,
                'sortable' => $this->attribute->sortable,
                'metadata' => [],
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexMetadata(string $user): void
    {
        $this->{$user}->givePermissionTo('attributes.show');

        /** @var Attribute $attribute */
        $attribute = Attribute::query()->create($this->attributeData);
        $attribute->metadata()->create([
            'name' => 'Dystrybucja',
            'value' => 'Polska',
            'value_type' => MetadataType::STRING,
            'public' => true,
        ]);

        $this
            ->actingAs($this->{$user})
            ->getJson('/attributes?metadata[Dystrybucja]=Polska')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(
                array_merge($this->attributeData, ['Dystrybucja' => 'Polska']),
            );
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexMetadataNotFound(string $user): void
    {
        $this->{$user}->givePermissionTo('attributes.show');

        /** @var Attribute $attribute */
        $attribute = Attribute::query()->create($this->attributeData);
        $attribute->metadata()->create([
            'name' => 'Dystrybucja',
            'value' => 'Francja',
            'value_type' => MetadataType::STRING,
            'public' => true,
        ]);

        $this
            ->actingAs($this->{$user})
            ->getJson('/attributes?metadata[Dystrybucja]=Polska')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexMetadataPrivate(string $user): void
    {
        $this->{$user}->givePermissionTo(['attributes.show', 'attributes.show_metadata_private']);

        /** @var Attribute $attribute */
        $attribute = Attribute::query()->create($this->attributeData);
        $attribute->metadata()->create([
            'name' => 'Dystrybucja',
            'value' => 'Polska',
            'value_type' => MetadataType::STRING,
            'public' => true,
        ]);

        $this
            ->actingAs($this->{$user})
            ->getJson('/attributes?metadata[Dystrybucja]=Polska')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(
                array_merge($this->attributeData, ['Dystrybucja' => 'Polska']),
            );
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexMetadataPrivateNotFound(string $user): void
    {
        $this->{$user}->givePermissionTo(['attributes.show', 'attributes.show_metadata_private']);

        /** @var Attribute $attribute */
        $attribute = Attribute::query()->create($this->attributeData);
        $attribute->metadata()->create([
            'name' => 'Dystrybucja',
            'value' => 'Francja',
            'value_type' => MetadataType::STRING,
            'public' => true,
        ]);

        $this
            ->actingAs($this->{$user})
            ->getJson('/attributes?metadata[Dystrybucja]=Polska')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }
}
