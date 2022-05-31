<?php

namespace Tests\Feature;

use App\Enums\DiscountTargetType;
use App\Enums\MetadataType;
use App\Models\Attribute;
use App\Models\AttributeOption;
use App\Models\Banner;
use App\Models\BannerMedia;
use App\Models\Country;
use App\Models\Discount;
use App\Models\Media;
use App\Models\Option;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\Product;
use App\Models\Schema;
use App\Models\ShippingMethod;
use App\Models\Status;
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
        $attributes = AttributeOption::factory()->count(2500)->create([
            'index' => 1,
            'attribute_id' => $attribute3->getKey(),
        ]);
        foreach ($attributes as $attribute) {
            $attribute->metadata()->create([
                'name' => 'test1',
                'value' => 0,
                'value_type' => MetadataType::NUMBER,
                'public' => true,
            ]);
        }

        $this
            ->actingAs($this->user)
            ->getJson('/attributes')
            ->assertOk();

        $this->assertQueryCountLessThan(11);
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

        $this->assertQueryCountLessThan(20);
    }

    public function testIndexPerformanceShippingMethode(): void
    {
        $this->user->givePermissionTo('shipping_methods.show');

        $shippingMethod = ShippingMethod::factory()->create();
        $shippingMethod->countries()->sync(Country::query()->select('code as country_code')->get()->toArray());

        $this->actingAs($this->user)->getJson('/shipping-methods')
            ->assertOk();

        // TODO: this should be improved
        $this->assertQueryCountLessThan(10);
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
}
