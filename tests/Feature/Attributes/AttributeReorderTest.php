<?php

namespace Tests\Feature\Attributes;

use App\Models\Attribute;
use App\Models\AttributeOption;
use Tests\TestCase;

class AttributeReorderTest extends TestCase
{
    /**
     * @dataProvider authProvider
     */
    public function testReorder(string $user): void
    {
        $this->$user->givePermissionTo('attributes.edit');

        $attributes = Attribute::factory()->count(3)->create();

        $this
            ->actingAs($this->$user)
            ->json('POST', '/attributes/reorder', [
                'ids' => $attributes->pluck('id')->toArray(),
            ])
            ->assertNoContent();

        $this->assertDatabaseHas('attributes', [
            'id' => $attributes[0]->getKey(),
            'order' => 0,
        ]);
        $this->assertDatabaseHas('attributes', [
            'id' => $attributes[1]->getKey(),
            'order' => 1,
        ]);
        $this->assertDatabaseHas('attributes', [
            'id' => $attributes[2]->getKey(),
            'order' => 2,
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testReorderOptions(string $user): void
    {
        $this->$user->givePermissionTo('attributes.edit');

        $attribute = Attribute::factory()->create();
        $options = AttributeOption::factory()->count(3)->create([
            'index' => 0,
            'attribute_id' => $attribute->getKey(),
        ]);

        $this
            ->actingAs($this->$user)
            ->json('POST', "/attributes/id:{$attribute->getKey()}/reorder", [
                'ids' => $options->pluck('id')->toArray(),
            ])
            ->assertNoContent();

        $this->assertDatabaseHas('attribute_options', [
            'id' => $options[0]->getKey(),
            'order' => 0,
        ]);
        $this->assertDatabaseHas('attribute_options', [
            'id' => $options[1]->getKey(),
            'order' => 1,
        ]);
        $this->assertDatabaseHas('attribute_options', [
            'id' => $options[2]->getKey(),
            'order' => 2,
        ]);
    }
}
