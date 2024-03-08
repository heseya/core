<?php

namespace Tests\Feature\Attributes;

use Domain\ProductAttribute\Models\Attribute;

class AttributeDeleteTest extends AttributeTestCase
{
    /**
     * @dataProvider authProvider
     */
    public function testDelete(string $user): void
    {
        $this->{$user}->givePermissionTo('attributes.remove');

        $this
            ->actingAs($this->{$user})
            ->deleteJson('/attributes/id:' . $this->attribute->getKey())
            ->assertNoContent();

        $this->assertDatabaseMissing('attributes', [
            'id' => $this->attribute->getKey(),
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testDeleteNotExistingAttribute(string $user): void
    {
        $this->{$user}->givePermissionTo('attributes.remove');

        Attribute::destroy($this->attribute->getKey());

        $this
            ->actingAs($this->{$user})
            ->deleteJson('/attributes/id:' . $this->attribute->getKey())
            ->assertNotFound();
    }

    /**
     * @dataProvider authProvider
     */
    public function testDeleteUnauthorized(string $user): void
    {
        $this
            ->actingAs($this->{$user})
            ->deleteJson('/attributes/id:' . $this->attribute->getKey())
            ->assertForbidden();
    }
}
