<?php

namespace Tests\Feature;

use App\Models\Attribute;
use Tests\TestCase;

class AttributeTest extends TestCase
{
    private Attribute $attribute;
    private array $newAttribute;

    public function setUp(): void
    {
        parent::setUp();

        $this->attribute = Attribute::factory()->create();

        $this->newAttribute = Attribute::factory()->definition();
    }

    public function testIndexUnauthorized()
    {
        $response = $this->getJson('/attributes');
        $response->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndex($user)
    {
        $this->$user->givePermissionTo('attributes.show');

        $response = $this
            ->actingAs($this->$user)
            ->getJson('/attributes');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreate($user)
    {
        $this->$user->givePermissionTo('attributes.add');

        $response = $this
            ->actingAs($this->$user)
            ->postJson('/attributes', $this->newAttribute);

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }
}
