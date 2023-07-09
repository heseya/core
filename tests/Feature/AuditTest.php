<?php

namespace Tests\Feature;

use App\Enums\ExceptionsEnums\Exceptions;
use App\Models\Product;
use App\Models\Tag;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class AuditTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Config::set('audit.console', true);
    }

    public function testViewUnauthorized(): void
    {
        $product = $this->createProduct();

        $this
            ->json('GET', '/audits/products/id:' . $product->getKey())
            ->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testView($user): void
    {
        $this->{$user}->givePermissionTo('audits.show');

        $product = $this->createProduct();

        $this
            ->actingAs($this->{$user})
            ->json('GET', '/audits/products/id:' . $product->getKey())
            ->assertOk()
            ->assertJsonFragment(['old_values' => ['name' => json_encode([$this->lang => 'Old name'])]])
            ->assertJsonFragment(['new_values' => ['name' => json_encode([$this->lang => 'New name'])]]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testViewWrongId($user): void
    {
        $this->{$user}->givePermissionTo('audits.show');

        $product = $this->createProduct();

        $this
            ->actingAs($this->{$user})
            ->json('GET', '/audits/products/id:its-not-id')
            ->assertNotFound();

        $this
            ->actingAs($this->{$user})
            ->json('GET', '/audits/products/id:' . $product->getKey() . $product->getKey())
            ->assertNotFound();
    }

    /**
     * @dataProvider authProvider
     */
    public function testViewNotAuditable($user): void
    {
        $this->{$user}->givePermissionTo('audits.show');

        $tag = Tag::factory()->create();
        $tag->update(['name' => 'test']);

        $this
            ->actingAs($this->{$user})
            ->json('GET', '/audits/tags/id:' . $tag->getKey())
            ->assertStatus(400)
            ->assertJsonFragment([
                'key' => Exceptions::coerce(Exceptions::CLIENT_MODEL_NOT_AUDITABLE)->key,
            ]);
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
}
