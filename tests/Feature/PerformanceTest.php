<?php

namespace Tests\Feature;

use App\Enums\DiscountTargetType;
use App\Enums\DiscountType;
use App\Enums\MetadataType;
use App\Models\Attribute;
use App\Models\AttributeOption;
use App\Models\Banner;
use App\Models\BannerMedia;
use App\Models\Country;
use App\Models\Discount;
use App\Models\Item;
use App\Models\Media;
use App\Models\Option;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\PriceRange;
use App\Models\Product;
use App\Models\ProductSet;
use App\Models\Schema;
use App\Models\ShippingMethod;
use App\Models\Status;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PerformanceTest extends TestCase
{
    use RefreshDatabase;

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

        $this->assertQueryCountLessThan(24);
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

        $this->assertQueryCountLessThan(11);
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

        $this->assertQueryCountLessThan(6);
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

        $this->assertQueryCountLessThan(9);
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
            ->getJson('/attributes/id:'. $attribute->getKey())
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

        $this->assertQueryCountLessThan(12);
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

        $this->assertQueryCountLessThan(21);
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
        $this->assertQueryCountLessThan(12);
    }

    public function testIndexPerformanceDiscount(): void
    {
        $this->user->givePermissionTo('sales.show_details');
        $discount = Discount::factory(['target_type' => DiscountTargetType::PRODUCTS, 'code' => null])->create();

        $products = Product::factory()->count(500)->create([
            'public' => true,
            'price' => 100,
            'price_min_initial' => 100,
            'price_max_initial' => 150,
        ]);

        $discount->products()->sync($products);

        $this->actingAs($this->user)
            ->json('GET', '/sales/id:' . $discount->getKey())
            ->assertOk();

        $this->assertQueryCountLessThan(16);
    }

    public function testCreateSalePerformance1000Products(): void
    {
        $this->user->givePermissionTo('sales.add');

        $set = ProductSet::factory()->create([
            'public' => true,
        ]);

        $products = Product::factory()
            ->count(1000)
            ->sequence(fn ($sequence) => ['slug' => $sequence->index])
            ->create([
                'public' => true,
                'price' => 1000,
                'price_min_initial' => 1200,
                'price_max_initial' => 1500,
            ]);

        $set->products()->sync($products);

        $sale = Discount::factory()->create([
            'name' => 'promocja -10',
            'type' => DiscountType::AMOUNT,
            'value' => 10,
            'priority' => 0,
            'target_type' => DiscountTargetType::PRODUCTS,
            'target_is_allow_list' => true,
            'code' => null,
        ]);

        $sale->productSets()->attach($set->getKey());

        $this->actingAs($this->user)->json('POST', '/sales', [
            'name' => 'promocja -10% priority 0',
            'type' => DiscountType::PERCENTAGE,
            'value' => 10,
            'priority' => 0,
            'target_type' => DiscountTargetType::PRODUCTS,
            'target_is_allow_list' => false,
        ])->assertCreated();

        $product = Product::first();

        $this->assertEquals(1071, $product->price_min);

        // Every product with discount +3 query to database (update, detach(sales), attach(sales))
        // 1000 products = +- 3137 queries, for 10000 +- 31130
        $this->assertQueryCountLessThan(3200);
    }

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
            'hide_on_index' => false,
        ]);

        $productItem = Item::factory()->create();

        $product = Product::factory()->create();
        $product->items()->sync([$productItem->getKey()]);
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
        $product2->items()->sync([$productItem->getKey()]);
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
        $product3->items()->sync([$productItem->getKey()]);
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
            'price' => 0,
            'hidden' => false,
            'required' => true,
        ]);
        $product->schemas()->sync([$schema->getKey()]);
        $product2->schemas()->sync([$schema->getKey()]);
        $product3->schemas()->sync([$schema->getKey()]);

        $option = $schema->options()->create([
            'name' => 'XL',
            'price' => 0,
        ]);
        $item = Item::factory()->create();
        $option->items()->sync([$item->getKey()]);

        $lowRange = PriceRange::create(['start' => 0]);
        $lowRange->prices()->create([
            'value' => rand(8, 15) + (rand(0, 99) / 100),
        ]);

        $highRange = PriceRange::create(['start' => 210]);
        $highRange->prices()->create(['value' => 0.0]);

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
            'value' => 100,
            'type' => DiscountType::PERCENTAGE,
            'target_type' => DiscountTargetType::SHIPPING_PRICE,
            'target_is_allow_list' => true,
        ]);

        $discountShipping->shippingMethods()->attach($shippingMethod);

        $discountOrder = Discount::factory()->create([
            'description' => 'Promocja na zamówienie',
            'code' => null,
            'value' => 100,
            'type' => DiscountType::AMOUNT,
            'target_type' => DiscountTargetType::ORDER_VALUE,
            'target_is_allow_list' => true,
        ]);

        $order->discounts()->attach(
            $discountShipping->getKey(),
            [
                'name' => $discountShipping->name,
                'type' => $discountShipping->type,
                'value' => $discountShipping->value,
                'target_type' => $discountShipping->target_type,
                'applied_discount' => $order->shipping_price_initial,
                'code' => $discountShipping->code,
            ],
            $discountOrder->getKey(),
            [
                'name' => $discountOrder->name,
                'type' => $discountOrder->type,
                'value' => $discountOrder->value,
                'target_type' => $discountOrder->target_type,
                'applied_discount' => $order->shipping_price_initial,
                'code' => $discountOrder->code,
            ]
        );

        $discountProduct = Discount::factory()->create([
            'description' => 'Testowy kupon',
            'code' => null,
            'value' => 47.47,
            'type' => DiscountType::AMOUNT,
            'target_type' => DiscountTargetType::PRODUCTS,
            'target_is_allow_list' => true,
        ]);

        $discountProduct->products()->attach($product);
        $discountProduct->products()->attach($product2);

        $discountProduct2 = Discount::factory()->create([
            'description' => 'Testowy kupon 2',
            'code' => 'O213D12',
            'value' => 10.00,
            'type' => DiscountType::AMOUNT,
            'target_type' => DiscountTargetType::PRODUCTS,
            'target_is_allow_list' => true,
        ]);

        $discountProduct2->products()->attach($product);
        $discountProduct2->products()->attach($product2);

        $sale = Discount::factory()->create([
            'description' => 'Promocja na wszystko',
            'code' => null,
            'value' => 10.00,
            'type' => DiscountType::AMOUNT,
            'target_type' => DiscountTargetType::PRODUCTS,
            'target_is_allow_list' => true,
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
        ]);

        $item_product->discounts()->attach([
            $discountProduct->getKey() => [
                'name' => $discountProduct->name,
                'type' => $discountProduct->type,
                'value' => $discountProduct->value,
                'target_type' => $discountProduct->target_type,
                'applied_discount' => $discountProduct->value,
                'code' => $discountProduct->code,
            ],
            $discountProduct2->getKey() => [
                'name' => $discountProduct2->name,
                'type' => $discountProduct2->type,
                'value' => $discountProduct2->value,
                'target_type' => $discountProduct2->target_type,
                'applied_discount' => $discountProduct2->value,
                'code' => $discountProduct2->code,
            ],
        ]);

        $item_product2 = $order->products()->create([
            'product_id' => $product2->getKey(),
            'quantity' => 1,
            'price' => 100.00,
            'price_initial' => 147.47,
            'name' => $product2->name,
        ]);

        $item_product2->discounts()->attach([
            $discountProduct->getKey() => [
                'name' => $discountProduct->name,
                'type' => $discountProduct->type,
                'value' => $discountProduct->value,
                'target_type' => $discountProduct->target_type,
                'applied_discount' => $discountProduct->value,
                'code' => $discountProduct->code,
            ],
            $discountProduct2->getKey() => [
                'name' => $discountProduct2->name,
                'type' => $discountProduct2->type,
                'value' => $discountProduct2->value,
                'target_type' => $discountProduct2->target_type,
                'applied_discount' => $discountProduct2->value,
                'code' => $discountProduct2->code,
            ],
        ]);

        $item_product3 = $order->products()->create([
            'product_id' => $product3->getKey(),
            'quantity' => 1,
            'price' => 100.00,
            'price_initial' => 147.47,
            'name' => $product3->name,
        ]);

        $item_product3->discounts()->attach([
            $discountProduct->getKey() => [
                'name' => $discountProduct->name,
                'type' => $discountProduct->type,
                'value' => $discountProduct->value,
                'target_type' => $discountProduct->target_type,
                'applied_discount' => $discountProduct->value,
                'code' => $discountProduct->code,
            ],
            $discountProduct2->getKey() => [
                'name' => $discountProduct2->name,
                'type' => $discountProduct2->type,
                'value' => $discountProduct2->value,
                'target_type' => $discountProduct2->target_type,
                'applied_discount' => $discountProduct2->value,
                'code' => $discountProduct2->code,
            ],
        ]);

        $this->actingAs($this->user)
            ->json('GET', '/orders/id:' . $order->getKey())->assertOk();

        // For 3 product, 2 discount on order and 2 discounts on products without load was 239 queries
        $this->assertQueryCountLessThan(103);
    }
}
