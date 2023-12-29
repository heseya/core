<?php

namespace Tests\Feature;

use App\Enums\DiscountTargetType;
use App\Enums\DiscountType;
use App\Enums\SchemaType;
use App\Models\Country;
use App\Models\Deposit;
use App\Models\Discount;
use App\Models\Item;
use App\Models\Media;
use App\Models\Option;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\Price;
use App\Models\PriceRange;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\Schema;
use App\Models\Status;
use App\Repositories\Contracts\ProductRepositoryContract;
use App\Repositories\DiscountRepository;
use App\Services\ProductService;
use App\Services\SchemaCrudService;
use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Money\Exception\UnknownCurrencyException;
use Brick\Money\Money;
use Domain\Banner\Models\Banner;
use Domain\Banner\Models\BannerMedia;
use Domain\Currency\Currency;
use Domain\Metadata\Enums\MetadataType;
use Domain\Metadata\Models\Metadata;
use Domain\Page\Page;
use Domain\Price\Dtos\PriceDto;
use Domain\Price\Enums\ProductPriceType;
use Domain\Price\PriceRepository;
use Domain\ProductAttribute\Models\Attribute;
use Domain\ProductAttribute\Models\AttributeOption;
use Domain\ProductSet\ProductSet;
use Domain\ShippingMethod\Models\ShippingMethod;
use Domain\Tag\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Tests\Utils\FakeDto;

class PerformanceTest extends TestCase
{
    use RefreshDatabase;

    private DiscountRepository $discountRepository;

    public function setUp(): void
    {
        parent::setUp();

        $this->discountRepository = App::make(DiscountRepository::class);
    }

    public function testIndexPerformanceProducts100(): void
    {
        $this->user->givePermissionTo('products.show');

        $this->prepareProducts();

        $this
            ->actingAs($this->user)
            ->json('GET', '/products?limit=100')
            ->assertOk()
            ->assertJsonCount(100, 'data');

        $this->assertQueryCountLessThan(20);
    }

    public function testIndexPerformanceProductsFull100(): void
    {
        $this->user->givePermissionTo('products.show');

        $this->prepareProducts();

        $this
            ->actingAs($this->user)
            ->json('GET', '/products?full=1&limit=100')
            ->assertOk()
            ->assertJsonCount(100, 'data');

        $this->assertQueryCountLessThan(55);
    }

    public function testShowProductPerformance(): void
    {
        $this->user->givePermissionTo('products.show_details');

        $this->prepareProducts(1);

        $product = Product::first();
        $this
            ->actingAs($this->user)
            ->json('GET', '/products/id:' . $product->getKey())
            ->assertOk();

        $this->assertQueryCountLessThan(64);
    }

    public function testShowProductWithAttributesPerformance(): void
    {
        $this->user->givePermissionTo('products.show');
        $this->user->givePermissionTo('products.show_details');
        $this->user->givePermissionTo('attributes.show');

        $this->prepareProducts(1);

        /** @var Product $product */
        $product = Product::first();

        $attribute1 = Attribute::factory()->create();
        $productAttribute1 = ProductAttribute::create([
            'product_id' => $product->getKey(),
            'attribute_id' => $attribute1->getKey(),
        ]);
        $options1 = AttributeOption::factory()->count(500)->create([
            'index' => 1,
            'attribute_id' => $attribute1->getKey(),
        ]);
        $productAttribute1->options()->attach($options1);

        $attribute2 = Attribute::factory()->create();
        $productAttribute2 = ProductAttribute::create([
            'product_id' => $product->getKey(),
            'attribute_id' => $attribute2->getKey(),
        ]);
        $options2 = AttributeOption::factory()->count(500)->create([
            'index' => 1,
            'attribute_id' => $attribute2->getKey(),
        ]);
        $productAttribute2->options()->attach($options2);

        $attribute3 = Attribute::factory()->create();
        $productAttribute3 = ProductAttribute::create([
            'product_id' => $product->getKey(),
            'attribute_id' => $attribute3->getKey(),
        ]);
        $options3 = AttributeOption::factory()->count(500)->create([
            'index' => 1,
            'attribute_id' => $attribute3->getKey(),
        ]);
        $productAttribute3->options()->attach($options3);

        DB::flushQueryLog();

        $response = $this->actingAs($this->user)
            ->json('GET', '/products/id:' . $product->getKey());
        $response->assertOk();
        $response->assertJsonCount(3, 'data.attributes');
        $this->assertQueryCountLessThan(67);

        $this->actingAs($this->user)
            ->json('GET', '/products/?' . Arr::query(['name' => $product->name]))
            ->assertOk();
        $this->assertQueryCountLessThan(15);

        $this->actingAs($this->user)
            ->json('GET', '/products/?' . Arr::query(['attribute_slug' => $attribute1->slug]))
            ->assertOk();
        $this->assertQueryCountLessThan(25);

        $this->actingAs($this->user)
            ->json('GET', '/products/?' . Arr::query(['name' => $product->name, 'full' => true]))
            ->assertOk();
        $this->assertQueryCountLessThan(49);
    }

    public function testIndexPerformanceSchema500(): void
    {
        $this->user->givePermissionTo('products.show_details');

        $product = Product::factory()->create([
            'public' => true,
        ]);

        $schema1 = Schema::factory()->create([
            'type' => 'select',
            'hidden' => false,
        ]);
        $schema2 = Schema::factory()->create([
            'type' => 'select',
            'hidden' => false,
        ]);
        $schema3 = Schema::factory()->create([
            'type' => 'select',
            'hidden' => false,
        ]);

        $product->schemas()->save($schema1);
        $product->schemas()->save($schema2);
        $product->schemas()->save($schema3);

        Option::factory()->count(500)->create([
            'schema_id' => $schema1->getKey(),
        ]);
        Option::factory()->count(500)->create([
            'schema_id' => $schema2->getKey(),
        ]);
        Option::factory()->count(500)->create([
            'schema_id' => $schema3->getKey(),
        ]);

        $this
            ->actingAs($this->user)
            ->json('GET', '/products/id:' . $product->getKey())
            ->assertOk();

        $this->assertQueryCountLessThan(37);
    }

    public function testIndexPerformanceListAttribute500(): void
    {
        $this->user->givePermissionTo('attributes.show');

        $attribute1 = Attribute::factory()->create();
        $attribute2 = Attribute::factory()->create();
        $attribute3 = Attribute::factory()->create();

        AttributeOption::factory()->count(500)->create([
            'index' => 1,
            'attribute_id' => $attribute1->getKey(),
        ]);
        AttributeOption::factory()->count(500)->create([
            'index' => 1,
            'attribute_id' => $attribute2->getKey(),
        ]);
        AttributeOption::factory()->count(500)->create([
            'index' => 1,
            'attribute_id' => $attribute3->getKey(),
        ]);

        $this
            ->actingAs($this->user)
            ->getJson('/attributes')
            ->assertOk();

        $this->assertQueryCountLessThan(10);
    }

    public function testShowPerformanceAttribute2500(): void
    {
        $this->user->givePermissionTo('attributes.show');

        $attribute = Attribute::factory()->create();

        AttributeOption::factory()->count(500)->create([
            'index' => 1,
            'attribute_id' => $attribute->getKey(),
        ]);

        $this
            ->actingAs($this->user)
            ->getJson('/attributes/id:' . $attribute->getKey())
            ->assertOk();

        $this->assertQueryCountLessThan(9);
    }

    public function testShowPerformanceListAttributeOptions2500(): void
    {
        $this->user->givePermissionTo('attributes.show');

        $attribute = Attribute::factory()->create();

        AttributeOption::factory()->count(500)->create([
            'index' => 1,
            'attribute_id' => $attribute->getKey(),
        ]);

        $this
            ->actingAs($this->user)
            ->getJson('/attributes/id:' . $attribute->getKey() . '/options')
            ->assertOk();

        $this->assertQueryCountLessThan(13);
    }

    public function testIndexPerformanceAttribute500(): void
    {
        $this->user->givePermissionTo('attributes.show');

        $attribute = Attribute::factory()->create();

        AttributeOption::factory()->count(500)->create([
            'index' => 1,
            'attribute_id' => $attribute->getKey(),
        ]);
        $this
            ->actingAs($this->user)
            ->getJson('/attributes/id:' . $attribute->getKey())
            ->assertOk();

        $this->assertQueryCountLessThan(9);
    }

    public function testIndexPerformanceBanner100(): void
    {
        $this->user->givePermissionTo('banners.show');

        $banner = Banner::factory()->create();
        $medias = Media::factory()->count(100)->create();

        $bannerMedia = BannerMedia::factory()->create([
            'banner_id' => $banner->getKey(),
            'title' => 'abc',
            'subtitle' => 'cba',
            'order' => 1,
        ]);
        $bannerMedia1 = BannerMedia::factory()->create([
            'banner_id' => $banner->getKey(),
            'title' => 'abc',
            'subtitle' => 'cba',
            'order' => 1,
        ]);
        $bannerMedia2 = BannerMedia::factory()->create([
            'banner_id' => $banner->getKey(),
            'title' => 'abc',
            'subtitle' => 'cba',
            'order' => 1,
        ]);

        $sync = [];
        foreach ($medias as $media) {
            $sync[$media->getKey()] = ['min_screen_width' => 100];
        }

        $bannerMedia->media()->sync($sync);
        $bannerMedia1->media()->sync($sync);
        $bannerMedia2->media()->sync($sync);

        $this
            ->actingAs($this->user)
            ->getJson('/banners')
            ->assertOk();

        $this->assertQueryCountLessThan(14);
    }

    public function testIndexPerformanceOrder500(): void
    {
        $this->user->givePermissionTo('orders.show');

        $shippingMethod = ShippingMethod::factory()->create();
        $status = Status::factory()->create();
        Product::factory()->count('500')->create();
        $order = Order::factory()->create([
            'shipping_method_id' => $shippingMethod->getKey(),
            'status_id' => $status->getKey(),
        ]);
        OrderProduct::factory()->count('100')->create([
            'order_id' => $order->getKey(),
        ]);

        $order->metadata()->create([
            'name' => 'Metadata',
            'value' => 'metadata test',
            'value_type' => MetadataType::STRING,
            'public' => true,
        ]);

        $this
            ->actingAs($this->user)
            ->getJson('/orders')
            ->assertOk();

        $this->assertQueryCountLessThan(26);
    }

    public function testIndexPerformanceShippingMethode(): void
    {
        $this->user->givePermissionTo('shipping_methods.show');

        $shippingMethod = ShippingMethod::factory()->create();
        $shippingMethod->countries()->sync(Country::query()->select('code as country_code')->get()->toArray());

        $this
            ->actingAs($this->user)
            ->json('GET', '/shipping-methods')
            ->assertOk();

        // TODO: this should be improved
        $this->assertQueryCountLessThan(17);
    }

    public function testShowPerformanceSale(): void
    {
        $this->user->givePermissionTo('sales.show_details');
        $discount = Discount::factory(['target_type' => DiscountTargetType::PRODUCTS, 'code' => null])->create();

        $products = Product::factory()->count(500)->create([
            'public' => true,
        ]);

        $discount->products()->sync($products);

        $this->actingAs($this->user)
            ->json('GET', '/sales/id:' . $discount->getKey())
            ->assertOk();

        // TODO: Fix with discounts refactor
        // It's baffling how slow this is (was 18 before)
        $this->assertQueryCountLessThan(2522);
    }

    public function testCreateSalePerformance1000Products(): void
    {
        $this->user->givePermissionTo('sales.add');

        $currency = Currency::DEFAULT;

        $set = ProductSet::factory()->create([
            'public' => true,
        ]);

        $products = Product::factory()
            ->count(1000)
            ->sequence(fn ($sequence) => ['slug' => $sequence->index])
            ->create([
                'public' => true,
            ]);

        /** @var ProductRepositoryContract $productRepository */
        $productRepository = App::make(ProductRepositoryContract::class);

        $products->each(function (Product $product) use ($productRepository) {
            $prices = array_map(fn (Currency $currency) => PriceDto::from(
                Money::of(round(mt_rand(500, 6000), -2), $currency->value),
            ), Currency::cases());

            $productRepository->setProductPrices($product->getKey(), [
                ProductPriceType::PRICE_BASE->value => $prices,
                ProductPriceType::PRICE_MIN->value => $prices,
                ProductPriceType::PRICE_MAX->value => $prices,
            ]);
        });

        $set->products()->sync($products);

        $sale = Discount::factory()->create([
            'name' => 'promocja -10',
            'priority' => 0,
            'target_type' => DiscountTargetType::PRODUCTS,
            'target_is_allow_list' => true,
            'code' => null,
            'percentage' => null,
        ]);

        $this->discountRepository->setDiscountAmounts($sale->getKey(), [
            PriceDto::from([
                'value' => '10.00',
                'currency' => $currency->value,
            ])
        ]);

        $sale->productSets()->attach($set->getKey());

        $this->actingAs($this->user)->json('POST', '/sales', [
            'translations' => [
                $this->lang => [
                    'name' => 'promocja -10% priority 0',
                ],
            ],
            'percentage' => '10.0',
            'priority' => 0,
            'target_type' => DiscountTargetType::PRODUCTS,
            'target_is_allow_list' => false,
        ])->assertCreated();

        // TODO: WTF?!
        // Every product with discount +3 query to database (update, detach(sales), attach(sales))
        // 1000 products = +- 3137 queries, for 10000 +- 31130
        // This is even worse now since prices live in a separate table, now there is a +1 query for every product
        // To dispatch ProductPriceUpdated +3 for each product, but it require prices so another +2 (old + new) for each product
        $this->assertQueryCountLessThan(11131);
    }

    /**
     * @throws UnknownCurrencyException
     * @throws RoundingNecessaryException
     * @throws NumberFormatException
     */
    public function testViewOrderPerformanceWithDiscounts(): void
    {
        $this->user->givePermissionTo('orders.show_details');

        $shippingMethod = ShippingMethod::factory()->create();

        $attribute = Attribute::factory()->create();
        AttributeOption::factory()->count(2)->create([
            'attribute_id' => $attribute->getKey(),
            'index' => 1,
        ]);

        $status = Status::factory()->create();
        $status->metadata()->create([
            'name' => 'Metadata',
            'value' => 'metadata test',
            'value_type' => MetadataType::STRING,
            'public' => true,
        ]);
        $status->metadata()->create([
            'name' => 'Metadata private',
            'value' => 'metadata test',
            'value_type' => MetadataType::STRING,
            'public' => false,
        ]);

        $tag = Tag::factory()->create();
        $set = ProductSet::factory()->create([
            'public' => true,
        ]);

        $productItem = Item::factory()->create();

        $product = Product::factory()->create();
        $product->items()->attach([
            $productItem->getKey() => [
                'required_quantity' => 1,
            ],
        ]);
        $product->attributes()->attach($attribute->getKey());
        $product->metadata()->create([
            'name' => 'Metadata',
            'value' => 'metadata test',
            'value_type' => MetadataType::STRING,
            'public' => true,
        ]);
        $product->metadata()->create([
            'name' => 'Metadata private',
            'value' => 'metadata test',
            'value_type' => MetadataType::STRING,
            'public' => false,
        ]);
        $product->tags()->sync($tag->getKey());
        $product->sets()->sync($set->getKey());

        $product2 = Product::factory()->create();
        $product2->items()->attach([
            $productItem->getKey() => [
                'required_quantity' => 1,
            ],
        ]);
        $product2->metadata()->create([
            'name' => 'Metadata',
            'value' => 'metadata test',
            'value_type' => MetadataType::STRING,
            'public' => true,
        ]);
        $product2->metadata()->create([
            'name' => 'Metadata private',
            'value' => 'metadata test',
            'value_type' => MetadataType::STRING,
            'public' => false,
        ]);
        $product2->tags()->sync($tag->getKey());
        $product2->sets()->sync($set->getKey());

        $product3 = Product::factory()->create();
        $product3->items()->attach([
            $productItem->getKey() => [
                'required_quantity' => 1,
            ],
        ]);
        $product3->metadata()->create([
            'name' => 'Metadata',
            'value' => 'metadata test',
            'value_type' => MetadataType::STRING,
            'public' => true,
        ]);
        $product3->metadata()->create([
            'name' => 'Metadata private',
            'value' => 'metadata test',
            'value_type' => MetadataType::STRING,
            'public' => false,
        ]);
        $product3->tags()->sync($tag->getKey());
        $product3->sets()->sync($set->getKey());

        $schema = Schema::factory()->create([
            'type' => 'select',
            'hidden' => false,
            'required' => true,
        ]);
        $schema->prices()->createMany(
            Price::factory(['value' => 0])->prepareForCreateMany()
        );
        $product->schemas()->sync([$schema->getKey()]);
        $product2->schemas()->sync([$schema->getKey()]);
        $product3->schemas()->sync([$schema->getKey()]);

        $option = $schema->options()->create([
            'name' => 'XL',
        ]);
        $option->prices()->createMany(
            Price::factory(['value' => 0])->prepareForCreateMany()
        );
        $item = Item::factory()->create();
        $option->items()->attach([
            $item->getKey() => [
                'required_quantity' => 1,
            ],
        ]);

        $currency = Currency::DEFAULT;
        $lowRange = PriceRange::create([
            'start' => Money::zero($currency->value),
            'value' => Money::of(mt_rand(8, 15) + (mt_rand(0, 99) / 100), $currency->value),
        ]);

        $highRange = PriceRange::create([
            'start' => Money::of(210, $currency->value),
            'value' => Money::zero($currency->value),
        ]);

        $shippingMethod->priceRanges()->saveMany([$lowRange, $highRange]);

        $order = Order::factory()->create([
            'shipping_method_id' => $shippingMethod->getKey(),
            'status_id' => $status->getKey(),
            'cart_total_initial' => 394.94,
            'cart_total' => 300.00,
            'summary' => 300.00,
            'shipping_price' => 0,
        ]);

        $order->metadata()->create([
            'name' => 'Metadata',
            'value' => 'metadata test',
            'value_type' => MetadataType::STRING,
            'public' => true,
        ]);

        $order->metadata()->create([
            'name' => 'Metadata private',
            'value' => 'metadata test',
            'value_type' => MetadataType::STRING,
            'public' => false,
        ]);

        $discountShipping = Discount::factory()->create([
            'description' => 'Testowy kupon',
            'code' => 'S43SA2',
            'percentage' => '100.00',
            'target_type' => DiscountTargetType::SHIPPING_PRICE,
            'target_is_allow_list' => true,
        ]);

        $discountShipping->shippingMethods()->attach($shippingMethod);

        $discountOrder = Discount::factory()->create([
            'description' => 'Promocja na zamówienie',
            'code' => null,
            'percentage' => null,
            'target_type' => DiscountTargetType::ORDER_VALUE,
            'target_is_allow_list' => true,
        ]);
        $this->discountRepository->setDiscountAmounts($discountOrder->getKey(), [
            PriceDto::from([
                'value' => '100.00',
                'currency' => $currency,
            ])
        ]);

        $order->discounts()->attach(
            $discountShipping->getKey(),
            [
                'name' => $discountShipping->name,
                'target_type' => $discountShipping->target_type,
                'applied' => $order->shipping_price_initial->getAmount(),
                'code' => $discountShipping->code,
                'currency' => $currency,
            ],
            $discountOrder->getKey(),
            [
                'name' => $discountOrder->name,
                'target_type' => $discountOrder->target_type,
                'applied' => $order->shipping_price_initial->getAmount(),
                'code' => $discountOrder->code,
                'currency' => $currency,
            ],
        );

        $discountProduct = Discount::factory()->create([
            'description' => 'Testowy kupon',
            'code' => null,
            'target_type' => DiscountTargetType::PRODUCTS,
            'target_is_allow_list' => true,
            'percentage' => null,
        ]);
        $this->discountRepository->setDiscountAmounts($discountProduct->getKey(), [
            PriceDto::from([
                'value' => '47.47',
                'currency' => $currency,
            ])
        ]);

        $discountProduct->products()->attach($product);
        $discountProduct->products()->attach($product2);

        $discountProduct2 = Discount::factory()->create([
            'description' => 'Testowy kupon 2',
            'code' => 'O213D12',
            'target_type' => DiscountTargetType::PRODUCTS,
            'target_is_allow_list' => true,
            'percentage' => null,
        ]);
        $this->discountRepository->setDiscountAmounts($discountProduct2->getKey(), [
            PriceDto::from([
                'value' => '10.00',
                'currency' => $currency,
            ])
        ]);

        $discountProduct2->products()->attach($product);
        $discountProduct2->products()->attach($product2);

        $sale = Discount::factory()->create([
            'description' => 'Promocja na wszystko',
            'code' => null,
            'target_type' => DiscountTargetType::PRODUCTS,
            'target_is_allow_list' => true,
            'percentage' => null,
        ]);
        $this->discountRepository->setDiscountAmounts($sale->getKey(), [
            PriceDto::from([
                'value' => '10.00',
                'currency' => $currency,
            ])
        ]);

        $sale->productSets()->attach($set);
        $sale->shippingMethods()->attach($shippingMethod);
        $sale->products()->attach($product);
        $sale->products()->attach($product2);

        $product->sales()->attach($sale);
        $product2->sales()->attach($sale);
        $product3->sales()->attach($sale);

        $item_product = $order->products()->create([
            'product_id' => $product->getKey(),
            'quantity' => 1,
            'price' => 200.00,
            'price_initial' => 247.47,
            'name' => $product->name,
            'currency' => $currency,
        ]);

        $item_product->discounts()->attach([
            $discountProduct->getKey() => [
                'name' => $discountProduct->name,
                'target_type' => $discountProduct->target_type,
                'applied' => '4747',
                'code' => $discountProduct->code,
                'currency' => $currency,
            ],
            $discountProduct2->getKey() => [
                'name' => $discountProduct2->name,
                'target_type' => $discountProduct2->target_type,
                'applied' => '1000',
                'code' => $discountProduct2->code,
                'currency' => $currency,
            ],
        ]);

        $item_product2 = $order->products()->create([
            'product_id' => $product2->getKey(),
            'quantity' => 1,
            'price' => 100.00,
            'price_initial' => 147.47,
            'name' => $product2->name,
            'currency' => $currency,
        ]);

        $item_product2->discounts()->attach([
            $discountProduct->getKey() => [
                'name' => $discountProduct->name,
                'target_type' => $discountProduct->target_type,
                'applied' => '4747',
                'code' => $discountProduct->code,
                'currency' => $currency,
            ],
            $discountProduct2->getKey() => [
                'name' => $discountProduct2->name,
                'target_type' => $discountProduct2->target_type,
                'applied' => '1000',
                'code' => $discountProduct2->code,
                'currency' => $currency,
            ],
        ]);

        $item_product3 = $order->products()->create([
            'product_id' => $product3->getKey(),
            'quantity' => 1,
            'price' => 100.00,
            'price_initial' => 147.47,
            'name' => $product3->name,
            'currency' => $currency,
        ]);

        $item_product3->discounts()->attach([
            $discountProduct->getKey() => [
                'name' => $discountProduct->name,
                'target_type' => $discountProduct->target_type,
                'applied' => '4747',
                'code' => $discountProduct->code,
                'currency' => $currency,
            ],
            $discountProduct2->getKey() => [
                'name' => $discountProduct2->name,
                'target_type' => $discountProduct2->target_type,
                'applied' => '1000',
                'code' => $discountProduct2->code,
                'currency' => $currency,
            ],
        ]);

        $this->actingAs($this->user)
            ->json('GET', '/orders/id:' . $order->getKey())->assertOk();

        // For 3 product, 2 discount on order and 2 discounts on products without load was 239 queries
        $this->assertQueryCountLessThan(61);
    }

    public function testViewItemPerformance(): void
    {
        $this->user->givePermissionTo('items.show_details');

        $attribute = Attribute::factory()->create();
        AttributeOption::factory()->count(2)->create([
            'attribute_id' => $attribute->getKey(),
            'index' => 1,
        ]);

        $tag = Tag::factory()->create();

        $set = ProductSet::factory()->create([
            'public' => true,
        ]);

        $productItem = Item::factory()->create();

        $product = Product::factory()->create(['public' => true]);
        $product->items()->attach([
            $productItem->getKey() => [
                'required_quantity' => 1,
            ],
        ]);
        $product->attributes()->attach($attribute->getKey());
        $product->metadata()->create([
            'name' => 'Metadata',
            'value' => 'metadata test',
            'value_type' => MetadataType::STRING,
            'public' => true,
        ]);
        $product->metadata()->create([
            'name' => 'Metadata private',
            'value' => 'metadata test',
            'value_type' => MetadataType::STRING,
            'public' => false,
        ]);
        $product->tags()->sync($tag->getKey());
        $product->sets()->sync($set->getKey());

        $product2 = Product::factory()->create(['public' => true]);
        $product2->items()->attach([
            $productItem->getKey() => [
                'required_quantity' => 1,
            ],
        ]);
        $product2->metadata()->create([
            'name' => 'Metadata',
            'value' => 'metadata test',
            'value_type' => MetadataType::STRING,
            'public' => true,
        ]);
        $product2->metadata()->create([
            'name' => 'Metadata private',
            'value' => 'metadata test',
            'value_type' => MetadataType::STRING,
            'public' => false,
        ]);
        $product2->tags()->sync($tag->getKey());
        $product2->sets()->sync($set->getKey());

        $product3 = Product::factory()->create(['public' => true]);
        $product3->items()->attach([
            $productItem->getKey() => [
                'required_quantity' => 1,
            ],
        ]);
        $product3->metadata()->create([
            'name' => 'Metadata',
            'value' => 'metadata test',
            'value_type' => MetadataType::STRING,
            'public' => true,
        ]);
        $product3->metadata()->create([
            'name' => 'Metadata private',
            'value' => 'metadata test',
            'value_type' => MetadataType::STRING,
            'public' => false,
        ]);
        $product3->tags()->sync($tag->getKey());
        $product3->sets()->sync($set->getKey());

        $schemaCrudService = App::make(SchemaCrudService::class);

        $schema = $schemaCrudService->store(
            FakeDto::schemaDto([
                'type' => 'select',
                'prices' => [['value' => 0, 'currency' => Currency::DEFAULT->value]],
                'hidden' => false,
                'required' => true,
                'options' => [
                    [
                        'name' => 'XL',
                        'prices' => [['value' => 0, 'currency' => Currency::DEFAULT->value]],
                    ],
                ],
            ])
        );
        $product->schemas()->sync([$schema->getKey()]);
        $product2->schemas()->sync([$schema->getKey()]);
        $product3->schemas()->sync([$schema->getKey()]);

        $item = Item::factory()->create();

        $option = $schema->options->where('name', 'XL')->first();
        $option->items()->attach([
            $item->getKey() => ['required_quantity' => 1],
            $productItem->getKey() => ['required_quantity' => 1],
        ]);

        $schema2 = $schemaCrudService->store(
            FakeDto::schemaDto([
                'type' => 'select',
                'prices' => [['value' => 0, 'currency' => Currency::DEFAULT->value]],
                'hidden' => false,
                'required' => false,
                'options' => [
                    [
                        'name' => 'XL',
                        'prices' => [['value' => 0, 'currency' => Currency::DEFAULT->value]],
                    ],
                    [
                        'name' => 'L',
                        'prices' => [['value' => 0, 'currency' => Currency::DEFAULT->value]],
                    ],
                ],
            ])
        );

        $option2 = $schema2->options->where('name', 'XL')->first();
        $option2->items()->attach([
            $productItem->getKey() => [
                'required_quantity' => 1,
            ],
        ]);

        $option3 = $schema2->options->where('name', 'L')->first();
        $option3->items()->attach([
            $productItem->getKey() => [
                'required_quantity' => 1,
            ],
        ]);

        $this
            ->actingAs($this->user)
            ->json('GET', '/items/id:' . $productItem->getKey())
            ->assertOk();

        $this->assertQueryCountLessThan(22);
    }

    private function prepareProducts(int $count = 100): void
    {
        $products = Product::factory()->count($count)->create([
            'public' => true,
        ]);

        $productSets = ProductSet::factory()->count(5)->create([
            'public' => true,
        ]);

        $categories = $productSets;
        foreach ($productSets as $set) {
            $children = ProductSet::factory([
                'parent_id' => $set->getKey(),
            ])->count(3)->create();
            $categories = $categories->merge($children);
        }

        $sales = Discount::factory()->count(5)->create([
            'code' => null,
        ]);

        $tags = Tag::factory()->count(10)->create();
        $pages = Page::factory()->count(10)->create();

        $items = Item::factory()->count(10)->create();

        /** @var ProductService $productService */
        $productService = App::make(ProductService::class);
        /** @var ProductRepositoryContract $productRepository */
        $productRepository = App::make(ProductRepositoryContract::class);
        $products->each(function (Product $product) use ($categories, $productService, $productRepository, $sales, $tags, $pages, $items) {
            $this->prepareProductSchemas($product);

            for ($i = 0; $i < 5; ++$i) {
                $media = Media::factory()->create();
                $product->media()->attach($media);
            }

            for ($i = 0; $i < 3; ++$i) {
                $product->sets()->syncWithoutDetaching($categories->random());
                $product->relatedSets()->syncWithoutDetaching($categories->random());
                $product->sales()->syncWithoutDetaching($sales->random());
                $product->tags()->syncWithoutDetaching($tags->random());
                $product->metadata()->save(Metadata::factory()->make());
                $product->pages()->syncWithoutDetaching($pages->random());
                $product->items()->attach($items->random(), ['required_quantity' => mt_rand(1, 4)]);
            }

            $product->save();
            $product->refresh();

            $prices = array_map(fn (Currency $currency) => PriceDto::from(
                Money::of(round(mt_rand(500, 6000), -2), $currency->value),
            ), Currency::cases());

            $productRepository->setProductPrices($product->getKey(), [
                ProductPriceType::PRICE_BASE->value => $prices,
                ProductPriceType::PRICE_MIN->value => $prices,
                ProductPriceType::PRICE_MAX->value => $prices,
            ]);

            $productService->updateMinMaxPrices($product);
        });
    }

    private function prepareProductSchemas(Product $product): void
    {
        $schemas = Schema::factory()
            ->has(Price::factory()->forAllCurrencies())
            ->count(7)
            ->sequence(fn ($sequence) => ['type' => $sequence->index])
            ->create();

        $schemas->each(function ($schema) use ($product) {
            $priceRepository = App::make(PriceRepository::class);
            $priceRepository->setModelPrices($schema, [
                ProductPriceType::PRICE_BASE->value => FakeDto::generatePricesInAllCurrencies(),
            ]);

            $product->schemas()->attach($schema->getKey());

            if ($schema->type->is(SchemaType::SELECT)) {
                /** @var Item $item */
                $item = Item::factory()->create();
                $item->deposits()->saveMany(Deposit::factory()->count(2)->make());

                Option::factory([
                    'schema_id' => $schema->getKey(),
                ])
                    ->has(Price::factory()->forAllCurrencies())
                    ->count(3)->create();
            }
        });
    }
}
