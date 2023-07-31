<?php

namespace Tests\Feature;

use App\Dtos\ProductCreateDto;
use App\Enums\Currency;
use App\Models\Product;
use App\Models\ProductSet;
use App\Services\Contracts\ProductServiceContract;
use Illuminate\Support\Facades\App;
use Tests\TestCase;

class ProductRelatedSetsTest extends TestCase
{
    private Product $product;

    public function setUp(): void
    {
        parent::setUp();

        /** @var ProductServiceContract $productService */
        $productService = App::make(ProductServiceContract::class);
        $this->product = $productService->create(ProductCreateDto::fake([
            'public' => true,
        ]));
    }

    /**
     * @dataProvider authProvider
     */
    public function testShowRelatedSets(string $user): void
    {
        $this->{$user}->givePermissionTo('products.show_details');

        $set1 = ProductSet::factory()->create([
            'public' => true,
        ]);
        $set2 = ProductSet::factory()->create([
            'public' => true,
        ]);

        $this->product->relatedSets()->sync([$set1->getKey(), $set2->getKey()]);

        $this
            ->actingAs($this->{$user})
            ->getJson('/products/' . $this->product->slug)
            ->assertOk()
            ->assertJsonFragment(['related_sets' => [
                [
                    'id' => $set1->getKey(),
                    'name' => $set1->name,
                    'slug' => $set1->slug,
                    'slug_suffix' => $set1->slugSuffix,
                    'slug_override' => $set1->slugOverride,
                    'public' => $set1->public,
                    'visible' => $set1->public_parent && $set1->public,
                    'parent_id' => $set1->parent_id,
                    'children_ids' => [],
                    'cover' => null,
                    'metadata' => [],
                ],
                [
                    'id' => $set2->getKey(),
                    'name' => $set2->name,
                    'slug' => $set2->slug,
                    'slug_suffix' => $set2->slugSuffix,
                    'slug_override' => $set2->slugOverride,
                    'public' => $set2->public,
                    'visible' => $set2->public_parent && $set2->public,
                    'parent_id' => $set2->parent_id,
                    'children_ids' => [],
                    'cover' => null,
                    'metadata' => [],
                ],
            ]]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testShowPrivateRelatedSetsNoPermission(string $user): void
    {
        $this->{$user}->givePermissionTo('products.show_details');

        $set1 = ProductSet::factory()->create([
            'public' => true,
        ]);
        $set2 = ProductSet::factory()->create([
            'public' => false,
        ]);

        $this->product->relatedSets()->sync([$set1->getKey(), $set2->getKey()]);

        $this
            ->actingAs($this->{$user})
            ->getJson('/products/' . $this->product->slug)
            ->assertOk()
            ->assertJsonFragment(['related_sets' => [
                [
                    'id' => $set1->getKey(),
                    'name' => $set1->name,
                    'slug' => $set1->slug,
                    'slug_suffix' => $set1->slugSuffix,
                    'slug_override' => $set1->slugOverride,
                    'public' => $set1->public,
                    'visible' => $set1->public_parent && $set1->public,
                    'parent_id' => $set1->parent_id,
                    'children_ids' => [],
                    'cover' => null,
                    'metadata' => [],
                ],
            ]]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testShowPrivateRelatedSetsWithPermission(string $user): void
    {
        $this->{$user}->givePermissionTo(['products.show_details', 'product_sets.show_hidden']);

        $set1 = ProductSet::factory()->create([
            'public' => true,
        ]);
        $set2 = ProductSet::factory()->create([
            'public' => false,
        ]);

        $this->product->relatedSets()->sync([$set1->getKey(), $set2->getKey()]);

        $this
            ->actingAs($this->{$user})
            ->getJson('/products/' . $this->product->slug)
            ->assertOk()
            ->assertJsonFragment(['related_sets' => [
                [
                    'id' => $set1->getKey(),
                    'name' => $set1->name,
                    'slug' => $set1->slug,
                    'slug_suffix' => $set1->slugSuffix,
                    'slug_override' => $set1->slugOverride,
                    'public' => $set1->public,
                    'visible' => $set1->public_parent && $set1->public,
                    'parent_id' => $set1->parent_id,
                    'children_ids' => [],
                    'cover' => null,
                    'metadata' => [],
                ],
                [
                    'id' => $set2->getKey(),
                    'name' => $set2->name,
                    'slug' => $set2->slug,
                    'slug_suffix' => $set2->slugSuffix,
                    'slug_override' => $set2->slugOverride,
                    'public' => $set2->public,
                    'visible' => $set2->public_parent && $set2->public,
                    'parent_id' => $set2->parent_id,
                    'children_ids' => [],
                    'cover' => null,
                    'metadata' => [],
                ],
            ]]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateWithRelatedSets(string $user): void
    {
        $this->{$user}->givePermissionTo('products.add');

        $set1 = ProductSet::factory()->create();
        $set2 = ProductSet::factory()->create();

        $prices = array_map(fn (Currency $currency) => [
            'value' => '10.00',
            'currency' => $currency->value,
        ], Currency::cases());

        $response = $this->actingAs($this->{$user})->postJson('/products', [
            'translations' => [
                $this->lang => [
                    'name' => 'Test',
                ],
            ],
            'published' => [$this->lang],
            'slug' => 'test',
            'prices_base' => $prices,
            'public' => false,
            'shipping_digital' => false,
            'related_sets' => [
                $set1->getKey(),
                $set2->getKey(),
            ],
        ]);

        $response->assertCreated();
        $product = $response->json('data.id');

        $this->assertDatabaseHas('related_product_sets', [
            'product_id' => $product,
            'product_set_id' => $set1->getKey(),
        ]);

        $this->assertDatabaseHas('related_product_sets', [
            'product_id' => $product,
            'product_set_id' => $set2->getKey(),
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateChangeRelatedSets(string $user): void
    {
        $this->{$user}->givePermissionTo('products.edit');

        $set1 = ProductSet::factory()->create();
        $set2 = ProductSet::factory()->create();
        $set3 = ProductSet::factory()->create();

        $this->product->relatedSets()->sync([$set1->getKey(), $set2->getKey()]);

        $response = $this->actingAs($this->{$user})->patchJson('/products/id:' . $this->product->getKey(), [
            'related_sets' => [
                $set2->getKey(),
                $set3->getKey(),
            ],
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('related_product_sets', [
            'product_id' => $this->product->getKey(),
            'product_set_id' => $set2->getKey(),
        ]);

        $this->assertDatabaseHas('related_product_sets', [
            'product_id' => $this->product->getKey(),
            'product_set_id' => $set3->getKey(),
        ]);

        $this->assertDatabaseMissing('related_product_sets', [
            'product_id' => $this->product->getKey(),
            'product_set_id' => $set1->getKey(),
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateDeleteRelatedSets(string $user): void
    {
        $this->{$user}->givePermissionTo('products.edit');

        $set1 = ProductSet::factory()->create();
        $set2 = ProductSet::factory()->create();

        $this->product->relatedSets()->sync([$set1->getKey(), $set2->getKey()]);

        $response = $this->actingAs($this->{$user})->patchJson('/products/id:' . $this->product->getKey(), [
            'related_sets' => [],
        ]);

        $response->assertOk();

        $this->assertDatabaseMissing('related_product_sets', [
            'product_id' => $this->product->getKey(),
        ]);
    }
}
