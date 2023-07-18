<?php

namespace Tests\Feature\Seo;

use App\Models\Product;
use App\Models\SeoMetadata;
use Illuminate\Support\Str;
use Tests\TestCase;

class SeoKeywordsTest extends TestCase
{
    /**
     * @dataProvider authProvider
     */
    public function testCheckKeywordsUnauthorized(string $user): void
    {
        $this
            ->actingAs($this->{$user})
            ->json('POST', '/seo/check', [
                'keywords' => ['Spring Boot'],
            ])->assertForbidden();
    }

    /**
     * @dataProvider authProvider
     */
    public function testCheckKeywordsNoDuplicates(string $user): void
    {
        $this->{$user}->givePermissionTo('seo.edit');
        $this
            ->actingAs($this->{$user})
            ->json('POST', '/seo/check', ['keywords' => ['test', 'test1']])
            ->assertOk()
            ->assertJsonFragment([
                'duplicated' => false,
                'duplicates' => [],
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCheckKeywordsDuplicates(string $user): void
    {
        /** @var SeoMetadata $seo */
        $seo = SeoMetadata::factory()->make([
            'keywords' => [
                $this->lang => ['test', 'test1'],
            ],
        ]);

        /** @var Product $product */
        $product = Product::factory()->create();
        $product->seo()->save($seo);

        $this->{$user}->givePermissionTo('seo.edit');
        $this
            ->actingAs($this->{$user})
            ->json('POST', '/seo/check', [
                'keywords' => ['test', 'test1'],
            ])->assertOk()->assertJsonFragment(['data' => [
                'duplicated' => true,
                'duplicates' => [
                    [
                        'id' => $product->getKey(),
                        'model_type' => Str::afterLast($product::class, '\\'),
                    ],
                ],
            ]]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCheckKeywordsDuplicatesWithExcluded(string $user): void
    {
        /** @var SeoMetadata $seo */
        $seo = SeoMetadata::factory()->make()->setTranslation('keywords', $this->lang, ['PHP', 'Laravel', 'Java']);

        /** @var Product $product */
        $product = Product::factory([
            'public' => true,
        ])->create();
        $product->seo()->save($seo);

        $product2 = Product::factory([
            'public' => true,
        ])->create();

        $this->{$user}->givePermissionTo('seo.edit');
        $this
            ->actingAs($this->{$user})
            ->json('POST', '/seo/check', [
                'keywords' => ['test', 'test1'],
                'excluded' => [
                    'id' => $product->getKey(),
                    'model' => 'Product',
                ],
            ])
            ->assertOk()
            ->assertJsonCount(1, 'data.duplicates')
            ->assertJsonFragment(['data' => [
                'duplicated' => true,
                'duplicates' => [
                    [
                        'id' => $product2->getKey(),
                        'model_type' => Str::afterLast($product2::class, '\\'),
                    ],
                ],
            ]]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateProduct(string $user): void
    {
        $product = Product::factory()->create();

        $this->{$user}->givePermissionTo('products.edit');
        $this
            ->actingAs($this->{$user})
            ->json('PATCH', "/products/id:{$product->getKey()}", ['seo' => [
                'translations' => [
                    'title' => 'product-title',
                    'description' => 'product-description',
                    'keywords' => ['product', 'key', 'words'],
                ],
                'header_tags' => ['meta' => ['name' => 'description', 'content' => 'My amazing site.']],
            ]])
            ->assertOk();

        $this->assertDatabaseHas('seo_metadata', [
            "title->{$this->lang}" => 'product-title',
            "description->{$this->lang}" => 'product-description',
            'global' => false,
            'model_id' => $product->getKey(),
            'model_type' => Product::class,
        ]);
    }
}
