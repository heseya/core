<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Tag;
use Tests\TestCase;

class AuditTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        config()->set('audit.console', true);
    }

    public function testViewUnauthorized(): void
    {
        $product = $this->createProduct();

        $this
            ->json('GET', '/audits/products/id:' . $product->getKey())
            ->assertForbidden();
    }

    private function createProduct(): Product
    {
        $product = Product::factory()->create([
            'name' => 'Old name',
        ]);

        $product->update([
            'name' => 'New name',
        ]);

        return $product;
    }

    public function testView(): void
    {
        $this->user->givePermissionTo('audits.show');

        $product =  $this->createProduct();

        $this
            ->actingAs($this->user)
            ->json('GET', '/audits/products/id:' . $product->getKey())
            ->assertOk()
            ->assertJsonFragment(['old_values' => ['name' => 'Old name']])
            ->assertJsonFragment(['new_values' => ['name' => 'New name']]);
    }

    public function testViewNotAuditable(): void
    {
        $this->user->givePermissionTo('audits.show');

        $tag = Tag::factory()->create();
        $tag->update(['name' => 'test']);

        $this
            ->actingAs($this->user)
            ->json('GET', '/audits/tags/id:' . $tag->getKey())
            ->assertStatus(400)
            ->assertJsonFragment(['message' => 'Model not auditable']);
    }

}
