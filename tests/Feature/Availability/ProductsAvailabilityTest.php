<?php

namespace Tests\Feature\Availability;

use App\Models\Product;
use App\Services\Contracts\AvailabilityServiceContract;
use Tests\TestCase;

class ProductsAvailabilityTest extends TestCase
{
    private AvailabilityServiceContract $availabilityService;

    public function setUp(): void
    {
        parent::setUp();

        $this->availabilityService = app(AvailabilityServiceContract::class);
    }

    public function testDigital(): void
    {
        $product = Product::factory()->create();

        $availability = $this->availabilityService->getCalculateProductAvailability($product);

        $this->assertTrue($availability['available']);
        $this->assertNull($availability['quantity']);
        $this->assertNull($availability['shipping_time']);
        $this->assertNull($availability['shipping_date']);
    }

}
