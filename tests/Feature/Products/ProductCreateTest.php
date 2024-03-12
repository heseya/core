<?php

namespace Tests\Feature\Products;

use App\Enums\ValidationError;
use App\Events\ProductCreated;
use App\Listeners\WebHookEventListener;
use App\Models\Media;
use App\Models\Product;
use App\Services\ProductService;
use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Money\Exception\UnknownCurrencyException;
use Domain\Currency\Currency;
use Domain\Page\Page;
use Domain\Product\Models\ProductBannerMedia;
use Heseya\Dto\DtoException;
use Illuminate\Events\CallQueuedListener;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Queue;
use Spatie\WebhookServer\CallWebhookJob;
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
                'banner' => [
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
     */
    public function testCreateProductBannerNull(string $user): void
    {
        $this->{$user}->givePermissionTo('products.add');

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
                'banner' => null,
            ])
            ->assertCreated()
            ->assertJsonFragment([
                'banner' => null,
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateProductBannerNoTitle(string $user): void
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
                'banner' => [
                    "translations" => [
                        $this->lang => [
                            'title' => null,
                            'name' => null,
                        ]
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
                'min_screen_width' => 1024,
            ]);

        $this->assertDatabaseCount('product_banner_responsive_media', 1);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateExistingSlug(string $user): void
    {
        $this->{$user}->givePermissionTo('products.add');

        /** @var ProductService $productService */
        $productService = App::make(ProductService::class);
        $productService->create(FakeDto::productCreateDto(['slug' => 'existing-slug']));

        $prices = array_map(fn (Currency $currency) => [
            'value' => '100.00',
            'currency' => $currency->value,
        ], Currency::cases());

        $this->actingAs($this->{$user})->json('POST', '/products', [
            'slug' => 'existing-slug',
            'prices_base' => $prices,
            'public' => true,
            'shipping_digital' => false,
            'translations' => [
                $this->lang => [
                    'name' => 'Test',
                    'description_html' => '<h1>Description</h1>',
                    'description_short' => 'So called short description...',
                ],
            ],
            'published' => [$this->lang],
        ])
            ->assertUnprocessable()
            ->assertJsonFragment([
                'key' => ValidationError::UNIQUE,
                'message' => 'The slug has already been taken.',
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testCreateExistingSlugDeleted(string $user): void
    {
        $this->{$user}->givePermissionTo('products.add');

        /** @var ProductService $productService */
        $productService = App::make(ProductService::class);
        $product = $productService->create(FakeDto::productCreateDto(['slug' => 'existing-slug']));

        $product->delete();

        $prices = array_map(fn (Currency $currency) => [
            'value' => '100.00',
            'currency' => $currency->value,
        ], Currency::cases());

        $this->actingAs($this->{$user})->json('POST', '/products', [
            'slug' => 'existing-slug',
            'prices_base' => $prices,
            'public' => true,
            'shipping_digital' => false,
            'translations' => [
                $this->lang => [
                    'name' => 'Test',
                    'description_html' => '<h1>Description</h1>',
                    'description_short' => 'So called short description...',
                ],
            ],
            'published' => [$this->lang],
        ])->assertCreated();
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
                'banner' => [
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
    public function testUpdateProductBannerNewNoTitle(string $user): void
    {
        $this->{$user}->givePermissionTo('products.edit');

        /** @var ProductService $productService */
        $productService = App::make(ProductService::class);
        $product = $productService->create(FakeDto::productCreateDto());

        $media = Media::factory()->create();

        $this
            ->actingAs($this->{$user})
            ->json('PATCH', "/products/id:{$product->getKey()}", [
                'banner' => [
                    "translations" => [
                        $this->lang => [
                            'title' => null,
                            'name' => null,
                        ]
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
                'min_screen_width' => 1024,
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
                'banner' => [
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
    public function testUpdateProductBannerExistingNoTitle(string $user): void
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
                'banner' => [
                    'title' => $bannerMedia->title,
                    'subtitle' => $bannerMedia->subtitle,
                    'url' => 'http://new.example.com',
                    'translations' => [
                        $this->lang => [
                            'title' => '',
                            'subtitle' => '',
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
                'min_screen_width' => 1024,
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
                'banner' => null,
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

    /**
     * @dataProvider authProvider
     */
    public function testUpdateExistingSlug(string $user): void
    {
        $this->{$user}->givePermissionTo('products.edit');

        /** @var ProductService $productService */
        $productService = App::make(ProductService::class);
        $productService->create(FakeDto::productCreateDto(['slug' => 'existing-slug']));
        $product = $productService->create(FakeDto::productCreateDto());

        $this->actingAs($this->{$user})->json('PATCH', '/products/id:' . $product->getKey(), [
            'slug' => 'existing-slug',
        ])
            ->assertUnprocessable()
            ->assertJsonFragment([
                'key' => ValidationError::UNIQUE,
                'message' => 'The slug has already been taken.',
            ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testUpdateExistingSlugDeleted(string $user): void
    {
        $this->{$user}->givePermissionTo('products.edit');

        /** @var ProductService $productService */
        $productService = App::make(ProductService::class);
        $existingSlug = $productService->create(FakeDto::productCreateDto(['slug' => 'existing-slug']));
        $product = $productService->create(FakeDto::productCreateDto());

        $existingSlug->delete();

        $this->actingAs($this->{$user})->json('PATCH', '/products/id:' . $product->getKey(), [
            'slug' => 'existing-slug',
        ])
            ->assertOk()
            ->assertJsonFragment([
                'slug' => 'existing-slug',
            ]);
    }
}
