<?php

namespace Tests\Feature\Attributes;

use App\Models\Attribute;
use App\Models\AttributeOption;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Tests\TestCase;

class AttributeReorderTest extends TestCase
{
    /**
     * @dataProvider authProvider
     */
    public function testReorder(string $user): void
    {
        $this->{$user}->givePermissionTo('attributes.edit');

        /** @var Collection<Attribute> $attributes */
        $attributes = Attribute::factory()->count(3)->create();

        $this
            ->actingAs($this->{$user})
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
        $this->{$user}->givePermissionTo('attributes.edit');

        $attribute = Attribute::factory()->create();

        /** @var Collection<AttributeOption> $options */
        $options = AttributeOption::factory()->count(3)->create([
            'index' => 0,
            'attribute_id' => $attribute->getKey(),
        ]);

        $this
            ->actingAs($this->{$user})
            ->json('POST', "/attributes/id:{$attribute->getKey()}/options/reorder", [
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

    /**
     * @dataProvider authProvider
     */
    public function testReorderOptionsNotRelated(string $user): void
    {
        $this->{$user}->givePermissionTo('attributes.edit');

        $attribute = Attribute::factory()->create();

        $this
            ->actingAs($this->{$user})
            ->json('POST', "/attributes/id:{$attribute->getKey()}/options/reorder", [
                'ids' => [Str::uuid()],
            ])
            ->assertStatus(422);
    }
}
