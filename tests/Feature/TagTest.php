<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Ramsey\Uuid\Uuid;
use Tests\TestCase;

class TagTest extends TestCase
{
    use RefreshDatabase;

    public function testIndexUnauthorized(): void
    {
        $this->getJson('/tags')->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexTagsShow($user): void
    {
        $this->{$user}->givePermissionTo('tags.show');

        $this->index($user);
    }

    public function index($user): void
    {
        $tag = Tag::factory()->count(10)->create()->random();

        $product = Product::factory()->create();
        $product->tags()->sync([$tag->getKey()]);

        $response = $this->actingAs($this->{$user})->getJson('/tags');

        $response
            ->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJson(['data' => [['id' => $tag->getKey()]]]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexByIds($user): void
    {
        $this->{$user}->givePermissionTo('tags.show');
        $tag = Tag::factory()->count(10)->create()->random();

        $product = Product::factory()->create();
        $product->tags()->sync([$tag->getKey()]);

        $response = $this->actingAs($this->{$user})->json('GET', '/tags', [
            'ids' => [
                $tag->getKey(),
            ],
        ]);

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJson(['data' => [['id' => $tag->getKey()]]]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexProductsAdd($user): void
    {
        $this->{$user}->givePermissionTo('products.add');

        $this->index($user);
    }

    /**
     * @dataProvider authProvider
     */
    public function testIndexProductsEdit($user): void
    {
        $this->{$user}->givePermissionTo('products.edit');

        $this->index($user);
    }

    public function testCreateUnauthorized(): void
    {
        $this->postJson('/tags')->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateTagsAdd($user): void
    {
        $this->{$user}->givePermissionTo('tags.add');

        $this->create($user);
    }

    public function create($user): void
    {
        $response = $this->actingAs($this->{$user})->postJson('/tags', [
            'name' => 'test sale',
            'color' => '444444',
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('tags', [
            'name' => 'test sale',
            'color' => '444444',
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateProductsAdd($user): void
    {
        $this->{$user}->givePermissionTo('products.add');

        $this->create($user);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateProductsEdit($user): void
    {
        $this->{$user}->givePermissionTo('products.edit');

        $this->create($user);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateWithId($user): void
    {
        $this->{$user}->givePermissionTo('products.add');

        $id = Uuid::uuid4()->toString();

        $response = $this->actingAs($this->{$user})->postJson('/tags', [
            'name' => 'test sale',
            'color' => '444444',
            'id' => $id,
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('tags', [
            'name' => 'test sale',
            'color' => '444444',
            'id' => $id,
        ]);
    }

    public function testUpdateUnauthorized(): void
    {
        $tag = Tag::factory()->create();

        $this->patchJson('/tags/id:' . $tag->getKey())->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdate($user): void
    {
        $this->{$user}->givePermissionTo('tags.edit');

        $tag = Tag::factory()->create();

        $response = $this->actingAs($this->{$user})->patchJson('/tags/id:' . $tag->getKey(), [
            'name' => 'test tag',
            'color' => 'ababab',
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('tags', [
            'name' => 'test tag',
            'color' => 'ababab',
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateWithEmptyData($user): void
    {
        $this->{$user}->givePermissionTo('tags.edit');

        $tag = Tag::factory()->create();

        $response = $this->actingAs($this->{$user})->patchJson('/tags/id:' . $tag->getKey(), []);

        $response->assertOk();

        $this->assertDatabaseHas('tags', [
            'name' => $tag->name,
            'color' => $tag->color,
        ]);
    }

    public function testDeleteUnauthorized(): void
    {
        $tag = Tag::factory()->create();

        $this->deleteJson('/tags/id:' . $tag->getKey())->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testDelete($user): void
    {
        $this->{$user}->givePermissionTo('tags.remove');

        $tag = Tag::factory()->create();

        $response = $this->actingAs($this->{$user})->deleteJson('/tags/id:' . $tag->getKey());

        $response->assertNoContent();

        $this->assertDatabaseMissing('tags', [
            'id' => $tag->getKey(),
        ]);
    }
}
