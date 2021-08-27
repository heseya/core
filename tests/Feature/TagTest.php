<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagTest extends TestCase
{
    use RefreshDatabase;

    public function testIndexUnauthorized(): void
    {
        $this->getJson('/tags')->assertForbidden();
    }

    public function testIndex(): void
    {
        $this->user->givePermissionTo('tags.show');

        $tag = Tag::factory()->count(10)->create()->random();

        $product = Product::factory()->create();
        $product->tags()->sync([$tag->getKey()]);

        $response = $this->actingAs($this->user)->getJson('/tags');

        $response
            ->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJson(['data' => [['id' => $tag->getKey()]]]);
    }

    public function testCreateUnauthorized(): void
    {
        $this->postJson('/tags')->assertForbidden();
    }

    public function testCreate(): void
    {
        $this->user->givePermissionTo('tags.add');

        $response = $this->actingAs($this->user)->postJson('/tags', [
            'name' => 'test sale',
            'color' => '444444',
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('tags', [
            'name' => 'test sale',
            'color' => '444444',
        ]);
    }

    public function testUpdateUnauthorized(): void
    {
        $tag = Tag::factory()->create();

        $this->patchJson('/tags/id:' . $tag->getKey())->assertForbidden();
    }

    public function testUpdate(): void
    {
        $this->user->givePermissionTo('tags.edit');

        $tag = Tag::factory()->create();

        $response = $this->actingAs($this->user)->patchJson('/tags/id:' . $tag->getKey(), [
            'name' => 'test tag',
            'color' => 'ababab',
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('tags', [
            'name' => 'test tag',
            'color' => 'ababab',
        ]);
    }

    public function testDeleteUnauthorized(): void
    {
        $tag = Tag::factory()->create();

        $this->deleteJson('/tags/id:' . $tag->getKey())->assertForbidden();
    }

    public function testDelete(): void
    {
        $this->user->givePermissionTo('tags.remove');

        $tag = Tag::factory()->create();

        $response = $this->actingAs($this->user)->deleteJson('/tags/id:' . $tag->getKey());

        $response->assertNoContent();

        $this->assertDatabaseMissing('tags', [
            'id' => $tag->getKey(),
        ]);
    }
}
