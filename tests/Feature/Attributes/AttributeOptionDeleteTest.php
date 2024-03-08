<?php

namespace Tests\Feature\Attributes;

use Domain\ProductAttribute\Models\Attribute;
use Domain\ProductAttribute\Models\AttributeOption;

class AttributeOptionDeleteTest extends AttributeOptionTestCase
{
    /**
     * @dataProvider authProvider
     */
    public function testDeleteOption(string $user): void
    {
        $this->{$user}->givePermissionTo('attributes.edit');

        $this
            ->actingAs($this->{$user})
            ->deleteJson('/attributes/id:' . $this->attribute->getKey() . '/options/id:' . $this->option->getKey())
            ->assertNoContent();

        $this->assertSoftDeleted($this->option);
    }

    /**
     * @dataProvider authProvider
     */
    public function testDeleteOptionReorder(string $user): void
    {
        $this->{$user}->givePermissionTo('attributes.edit');

        $option1 = AttributeOption::factory()->create([
            'index' => 2,
            'attribute_id' => $this->attribute->getKey(),
            'order' => 1,
        ]);

        $option2 = AttributeOption::factory()->create([
            'index' => 3,
            'attribute_id' => $this->attribute->getKey(),
            'order' => 2,
        ]);

        $this
            ->actingAs($this->{$user})
            ->deleteJson('/attributes/id:' . $this->attribute->getKey() . '/options/id:' . $this->option->getKey())
            ->assertNoContent();

        $this->assertSoftDeleted($this->option);

        $this->assertDatabaseHas('attribute_options', [
            'id' => $option1->getKey(),
            'index' => 2,
            'order' => 0,
        ]);

        $this->assertDatabaseHas('attribute_options', [
            'id' => $option2->getKey(),
            'index' => 3,
            'order' => 1,
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testDeleteOptionNotExisting(string $user): void
    {
        $this->{$user}->givePermissionTo('attributes.edit');

        $this->option->delete();

        $this
            ->actingAs($this->{$user})
            ->deleteJson('/attributes/id:' . $this->attribute->getKey() . '/options/id:' . $this->option->getKey())
            ->assertNotFound();
    }

    /**
     * @dataProvider authProvider
     */
    public function testDeleteOptionNotRelatedOption(string $user): void
    {
        $this->{$user}->givePermissionTo('attributes.edit');

        $attribute = Attribute::factory()->create();

        $this
            ->actingAs($this->{$user})
            ->deleteJson('/attributes/id:' . $attribute->getKey() . '/options/id:' . $this->option->getKey())
            ->assertNotFound();
    }

    /**
     * @dataProvider authProvider
     */
    public function testDeleteOptionUnauthorized(string $user): void
    {
        $this
            ->actingAs($this->{$user})
            ->deleteJson('/attributes/id:' . $this->attribute->getKey() . '/options/id:' . $this->option->getKey())
            ->assertForbidden();
    }
}
