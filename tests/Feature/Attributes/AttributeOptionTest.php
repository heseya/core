<?php

namespace Tests\Feature\Attributes;

use App\Models\Attribute;
use App\Models\AttributeOption;
use Tests\TestCase;

class AttributeOptionTest extends TestCase
{
    /**
     * @dataProvider authProvider
     */
    public function testSearch($user): void
    {
        $this->$user->givePermissionTo('attributes.show');

        $attribute = Attribute::factory()->create();
        $target = AttributeOption::factory()->create([
            'name' => 'searchtarget',
            'attribute_id' => $attribute->getKey(),
            'index' => 0,
        ]);

        AttributeOption::factory()->count(10)->create([
            'name' => 'test',
            'attribute_id' => $attribute->getKey(),
            'index' => 0,
        ]);

        $this
            ->actingAs($this->$user)
            ->json('GET', "/attributes/id:{$attribute->getKey()}/options", [
                'search' => 'searchtarget',
            ])
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['name' => $target->name]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testSearchByIds($user): void
    {
        $this->$user->givePermissionTo('attributes.show');

        $attribute = Attribute::factory()->create();
        $target = AttributeOption::factory()->create([
            'name' => 'searchtarget',
            'attribute_id' => $attribute->getKey(),
            'index' => 0,
        ]);

        AttributeOption::factory()->count(10)->create([
            'name' => 'test',
            'attribute_id' => $attribute->getKey(),
            'index' => 0,
        ]);

        $this
            ->actingAs($this->$user)
            ->json('GET', "/attributes/id:{$attribute->getKey()}/options", [
                'ids' => [
                    $target->getKey(),
                ],
            ])
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['name' => $target->name]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testSearchNoString($user): void
    {
        $this->$user->givePermissionTo('attributes.show');

        $attribute = Attribute::factory()->create();

        $this
            ->actingAs($this->$user)
            ->json('GET', "/attributes/id:{$attribute->getKey()}/options", [
                'search' => null,
            ])
            ->assertUnprocessable();
    }
}
