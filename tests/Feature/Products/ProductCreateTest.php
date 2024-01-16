<?php

namespace Tests\Feature\Products;

use App\Models\Media;
use App\Services\ProductService;
use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Money\Exception\UnknownCurrencyException;
use Domain\Currency\Currency;
use Domain\Page\Page;
use Domain\Product\Models\ProductBannerMedia;
use Heseya\Dto\DtoException;
use Illuminate\Support\Facades\App;
use Tests\TestCase;
use Tests\Utils\FakeDto;

class ProductCreateTest extends TestCase
{
    /**
     * @dataProvider authProvider
     */
    public function testCreateDescriptions(string $user): void
    {
        $page = Page::factory()->create();

        $prices = array_map(fn (Currency $currency) => [
            'value' => '100.00',
            'currency' => $currency->value,
        ], Currency::cases());

        $this->{$user}->givePermissionTo('products.add');
        $response = $this
            ->actingAs($this->{$user})
            ->json('POST', '/products', [
                'translations' => [
                    $this->lang => [
                        'name' => 'Test',
                    ],
                ],
                'published' => [$this->lang],
                'slug' => 'slug',
                'prices_base' => $prices,
                'public' => true,
                'shipping_digital' => false,
                'descriptions' => [$page->getKey()],
            ])
            ->assertCreated()
            ->assertJsonPath('data.descriptions.0.id', $page->getKey());

        $this->assertDatabaseHas('product_page', [
            'product_id' => $response->json('data.id'),
            'page_id' => $page->getKey(),
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateProductBanner(string $user): void
    {
        $this->{$user}->givePermissionTo('products.add');

        $media = Media::factory()->create();

        $prices = array_map(fn (Currency $currency) => [
            'value' => '100.00',
            'currency' => $currency->value,
        ], Currency::cases());

        $this
            ->actingAs($this->{$user})
            ->json('POST', '/products', [
                'translations' => [
                    $this->lang => [
                        'name' => 'Test',
                    ],
                ],
                'published' => [$this->lang],
                'slug' => 'slug',
                'prices_base' => $prices,
                'public' => true,
                'shipping_digital' => false,
                'banner_media' => [
                    'url' => 'http://example.com',
                    'translations' => [
                        $this->lang => [
                            'title' => 'banner title',
                            'subtitle' => 'banner subtitle',
                        ],
                    ],
                    'media' => [
                        [
                            'min_screen_width' => 1024,
                            'media' => $media->getKey(),
                        ],
                    ],
                ],
            ])
            ->assertCreated()
            ->assertJsonFragment([
                'url' => 'http://example.com',
                'title' => 'banner title',
                'subtitle' => 'banner subtitle',
            ])
            ->assertJsonFragment([
                'min_screen_width' => 1024,
            ]);

        $this->assertDatabaseHas('product_banner_media', [
            "title->{$this->lang}" => 'banner title',
            "subtitle->{$this->lang}" => 'banner subtitle',
        ]);
        $this->assertDatabaseCount('product_banner_responsive_media', 1);
    }

    /**
     * @dataProvider authProvider
     *
     * @throws NumberFormatException
     * @throws RoundingNecessaryException
     * @throws UnknownCurrencyException
     * @throws DtoException
     */
    public function testUpdateDescriptions(string $user): void
    {
        /** @var ProductService $productService */
        $productService = App::make(ProductService::class);
        $product = $productService->create(FakeDto::productCreateDto());
        $page = Page::factory()->create();

        $this->{$user}->givePermissionTo('products.edit');
        $response = $this
            ->actingAs($this->{$user})
            ->json('PATCH', "/products/id:{$product->getKey()}", [
                'descriptions' => [$page->getKey()],
            ]);

        $response->assertOk()
            ->assertJsonPath('data.descriptions.0.id', $page->getKey());

        $this->assertDatabaseHas('product_page', [
            'product_id' => $response->json('data.id'),
            'page_id' => $page->getKey(),
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateProductBannerNew(string $user): void
    {
        $this->{$user}->givePermissionTo('products.edit');

        /** @var ProductService $productService */
        $productService = App::make(ProductService::class);
        $product = $productService->create(FakeDto::productCreateDto());

        $media = Media::factory()->create();

        $this
            ->actingAs($this->{$user})
            ->json('PATCH', "/products/id:{$product->getKey()}", [
                'banner_media' => [
                    'url' => 'http://example.com',
                    'translations' => [
                        $this->lang => [
                            'title' => 'banner title',
                            'subtitle' => 'banner subtitle',
                        ],
                    ],
                    'media' => [
                        [
                            'min_screen_width' => 1024,
                            'media' => $media->getKey(),
                        ],
                    ],
                ],
            ])
            ->assertOk()
            ->assertJsonFragment([
                'url' => 'http://example.com',
                'title' => 'banner title',
                'subtitle' => 'banner subtitle',
            ])
            ->assertJsonFragment([
                'min_screen_width' => 1024,
            ]);

        $this->assertDatabaseHas('product_banner_media', [
            "title->{$this->lang}" => 'banner title',
            "subtitle->{$this->lang}" => 'banner subtitle',
        ]);
        $this->assertDatabaseCount('product_banner_responsive_media', 1);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateProductBannerExisting(string $user): void
    {
        $this->{$user}->givePermissionTo('products.edit');

        /** @var ProductBannerMedia $bannerMedia */
        $bannerMedia = ProductBannerMedia::factory()->create();

        $media = Media::factory()->create();
        $bannerMedia->media()->attach([$media->getKey() => ['min_screen_width' => 512]]);

        /** @var ProductService $productService */
        $productService = App::make(ProductService::class);
        $product = $productService->create(FakeDto::productCreateDto());
        $bannerMedia->product()->save($product);

        $newMedia = Media::factory()->create();

        $this
            ->actingAs($this->{$user})
            ->json('PATCH', "/products/id:{$product->getKey()}", [
                'banner_media' => [
                    'url' => 'http://new.example.com',
                    'translations' => [
                        $this->lang => [
                            'title' => 'new title',
                            'subtitle' => 'new subtitle',
                        ],
                    ],
                    'media' => [
                        [
                            'min_screen_width' => 1024,
                            'media' => $newMedia->getKey(),
                        ],
                    ],
                ],
            ])
            ->assertOk()
            ->assertJsonFragment([
                'url' => 'http://new.example.com',
                'title' => 'new title',
                'subtitle' => 'new subtitle',
            ])
            ->assertJsonFragment([
                'min_screen_width' => 1024,
            ]);

        $this->assertDatabaseHas('product_banner_media', [
            'id' => $bannerMedia->getKey(),
            "title->{$this->lang}" => 'new title',
            "subtitle->{$this->lang}" => 'new subtitle',
        ]);

        $this->assertDatabaseHas('product_banner_responsive_media', [
            'product_banner_media_id' => $bannerMedia->getKey(),
            'media_id' => $newMedia->getKey(),
        ]);

        $this->assertDatabaseMissing('product_banner_responsive_media', [
            'product_banner_media_id' => $bannerMedia->getKey(),
            'media_id' => $media->getKey(),
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateProductBannerRemove(string $user): void
    {
        $this->{$user}->givePermissionTo('products.edit');

        /** @var ProductBannerMedia $bannerMedia */
        $bannerMedia = ProductBannerMedia::factory()->create();

        $media = Media::factory()->create();
        $bannerMedia->media()->attach([$media->getKey() => ['min_screen_width' => 512]]);

        /** @var ProductService $productService */
        $productService = App::make(ProductService::class);
        $product = $productService->create(FakeDto::productCreateDto());
        $bannerMedia->product()->save($product);

        $this
            ->actingAs($this->{$user})
            ->json('PATCH', "/products/id:{$product->getKey()}", [
                'banner_media' => null,
            ])
            ->assertOk()
            ->assertJsonMissing([
                'url' => 'http://new.example.com',
                'title' => 'new title',
                'subtitle' => 'new subtitle',
            ])
            ->assertJsonMissing([
                'min_screen_width' => 1024,
            ]);

        $this->assertDatabaseMissing('product_banner_media', [
            'id' => $bannerMedia->getKey(),
            "title->{$this->lang}" => 'new title',
            "subtitle->{$this->lang}" => 'new subtitle',
        ]);

        $this->assertDatabaseMissing('product_banner_responsive_media', [
            'product_banner_media_id' => $bannerMedia->getKey(),
            'media_id' => $media->getKey(),
        ]);
    }
}
