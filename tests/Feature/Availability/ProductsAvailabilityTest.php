<?php

namespace Tests\Feature\Availability;

use App\Enums\SchemaType;
use App\Models\Item;
use App\Models\Option;
use App\Models\Product;
use App\Models\Schema;
use App\Services\Contracts\AvailabilityServiceContract;
use Tests\TestCase;
use Tests\Traits\InteractsWithInaccessibleMethods;

class ProductsAvailabilityTest extends TestCase
{
    use InteractsWithInaccessibleMethods;

    private AvailabilityServiceContract $availabilityService;

    public function setUp(): void
    {
        parent::setUp();
        $this->availabilityService = app(AvailabilityServiceContract::class);
    }

    // Product not have any schema or items related
    public function testDigital(): void
    {
        $product = Product::factory()->create();

        $availability = $this->availabilityService->getCalculateProductAvailability($product);

        $this->assertTrue($availability['available']);
        $this->assertNull($availability['quantity']);
        $this->assertNull($availability['shipping_time']);
        $this->assertNull($availability['shipping_date']);
        $this->assertEmpty($availability['productAvailabilities']);
    }

    // Product have only non required schemas
    public function testNoRequiredSchemas(): void
    {
        /** @var Product $product */
        $product = Product::factory()->create();
        $item = Item::factory()->create();

        $schema = Schema::factory()->create([
            'required' => false,
            'type' => SchemaType::SELECT,
        ]);
        /** @var Option $option */
        $option = Option::factory()->create([
            'schema_id' => $schema->getKey(),
        ]);
        $option->items()->attach($item->getKey());
        $product->schemas()->attach($schema->getKey());

        $availability = $this->availabilityService->getCalculateProductAvailability($product);

        $this->assertTrue($availability['available']);
        $this->assertEquals(null, $availability['quantity']);
        $this->assertNull($availability['shipping_time']);
        $this->assertNull($availability['shipping_date']);
        $this->assertEmpty($availability['productAvailabilities']);
    }

    // Product have schema and options but without any items
    public function testRequiredSchemasNoItems(): void
    {
        /** @var Schema $schema */
        $schema = Schema::factory()->create([
            'required' => true,
            'type' => SchemaType::SELECT,
        ]);
        Option::factory()->create([
            'schema_id' => $schema->getKey(),
        ]);

        /** @var Product $product */
        $product = Product::factory()->create();
        $product->schemas()->attach($schema->getKey());

        // Check schema finder
        $requiredSchemas = $this->invokeMethod(
            $this->availabilityService,
            'getRequiredSchemasWithItems',
            [$product],
        );
        $this->assertTrue($requiredSchemas->isEmpty());

        $availability = $this->availabilityService->getCalculateProductAvailability($product);

        $this->assertTrue($availability['available']);
        $this->assertEquals(null, $availability['quantity']);
        $this->assertNull($availability['shipping_time']);
        $this->assertNull($availability['shipping_date']);
        $this->assertEmpty($availability['productAvailabilities']);
    }

    // Product have schema and options with required items but item is not available
    public function testRequiredSchemasNoAvailableItems(): void
    {
        /** @var Product $product */
        $product = Product::factory()->create();
        $item = Item::factory()->create();

        $schema1 = Schema::factory()->create([
            'required' => true,
            'type' => SchemaType::SELECT,
        ]);
        /** @var Option $option */
        $option = Option::factory()->create([
            'schema_id' => $schema1->getKey(),
        ]);
        $option->items()->attach($item->getKey());
        $item = Item::factory()->create();
        $option = Option::factory()->create([
            'schema_id' => $schema1->getKey(),
        ]);
        $option->items()->attach($item->getKey());
        $product->schemas()->attach($schema1->getKey());

        $schema = Schema::factory()->create([
            'required' => true,
            'type' => SchemaType::SELECT,
        ]);
        $item = Item::factory()->create();
        $option = Option::factory()->create([
            'schema_id' => $schema->getKey(),
        ]);
        $option->items()->attach($item->getKey());
        $item = Item::factory()->create();
        $option = Option::factory()->create([
            'schema_id' => $schema->getKey(),
        ]);
        $option->items()->attach($item->getKey());
        $item = Item::factory()->create();
        $option = Option::factory()->create([
            'schema_id' => $schema->getKey(),
        ]);
        $option->items()->attach($item->getKey());
        $product->schemas()->sync([$schema->getKey(), $schema1->getKey()]);

        $availability = $this->availabilityService->getCalculateProductAvailability($product);

        $this->assertFalse($availability['available']);
        $this->assertEquals(0, $availability['quantity']);
        $this->assertNull($availability['shipping_time']);
        $this->assertNull($availability['shipping_date']);
        $this->assertEmpty($availability['productAvailabilities']);
    }

    // 10 items in warehouse but required quantity is 4 so only 2 products should be avaiable
    public function testItem(): void
    {
        /** @var Product $product */
        $product = Product::factory()->create();
        /** @var Item $item */
        $item = Item::factory()->create();
        $item->deposits()->create(['quantity' => 10]);
        $product->items()->attach([$item->getKey() => ['required_quantity' => 4]]);

        $availability = $this->availabilityService->getCalculateProductAvailability($product);

        $this->assertTrue($availability['available']);
        $this->assertEquals(2, $availability['quantity']);
        $this->assertNull($availability['shipping_time']);
        $this->assertNull($availability['shipping_date']);
        $this->assertEmpty($availability['productAvailabilities']);
    }
}
