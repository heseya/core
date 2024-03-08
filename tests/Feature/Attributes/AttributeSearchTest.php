<?php

namespace Tests\Feature\Attributes;

use Domain\ProductAttribute\Models\Attribute;

class AttributeSearchTest extends AttributeTestCase
{
    /**
     * @dataProvider authProvider
     */
    public function testSearch(string $user): void
    {
        $this->{$user}->givePermissionTo('attributes.show');
        $first = Attribute::factory()->create(['name' => 'here will by description for test']);
        Attribute::factory()->create([
            'name' => 'new name',
            'description' => 'new description',
        ]);
        Attribute::factory()->create([
            'name' => 'new name test',
            'description' => 'test',
            'slug' => 'description',
        ]);

        Attribute::factory()->create(['name' => 'new name test ' . $first->getKey()]);

        $this
            ->actingAs($this->{$user})
            ->json('GET', '/attributes', ['search' => 'description'])
            ->assertOk()
            ->assertJsonCount(3, 'data');

        $this
            ->actingAs($this->{$user})
            ->json('GET', '/attributes', ['search' => $first->getKey()])
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    /**
     * @dataProvider authProvider
     */
    public function testSearchGlobal(string $user): void
    {
        $this->{$user}->givePermissionTo('attributes.show');

        Attribute::factory()->create([
            'name' => 'global',
            'description' => 'new description',
            'global' => true,
        ]);
        Attribute::factory()->create([
            'name' => 'no global',
            'description' => 'test',
            'slug' => 'description',
            'global' => false,
        ]);

        $this
            ->actingAs($this->{$user})
            ->json('GET', '/attributes', ['global' => true])
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment([
                'name' => 'global',
            ])
            ->assertJsonFragment([
                'id' => $this->attribute->getKey(),
                'name' => $this->attribute->name,
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testSearchSortable(string $user): void
    {
        $this->{$user}->givePermissionTo('attributes.show');

        Attribute::factory()->create([
            'name' => 'sortable',
            'description' => 'new description',
            'sortable' => true,
        ]);
        Attribute::factory()->create([
            'name' => 'no sortable',
            'description' => 'test',
            'slug' => 'description',
            'sortable' => false,
        ]);

        $this
            ->actingAs($this->{$user})
            ->json('GET', '/attributes', ['sortable' => true])
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonFragment([
                'name' => 'sortable',
            ])
            ->assertJsonFragment([
                'id' => $this->attribute->getKey(),
                'name' => $this->attribute->name,
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testSearchNotFound(string $user): void
    {
        $this->{$user}->givePermissionTo('attributes.show');

        Attribute::create($this->attributeData);

        $this
            ->actingAs($this->{$user})
            ->json('GET', '/attributes', ['search' => 'abc not found in search'])
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }
}
