<?php

namespace Tests\Unit\Availability;

use App\Models\Item;
use App\Services\AvailabilityService;
use App\Services\DepositService;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

class OptionsAvailabilityTest extends TestCase
{
    use ItemCreate;

    protected AvailabilityService $availabilityService;

    public function __construct(?string $name = null, array $data = [], $dataName = '')
    {
        parent::__construct($name, $data, $dataName);

        $this->availabilityService = new AvailabilityService(new DepositService());
        Item::disableAuditing();
    }

    /**
     * Digital items
     * Without any items Option should be available
     */
    public function testNoItems(): void
    {
        $option = $this->createOption();

        $availability = $this->availabilityService->getCalculateOptionAvailability($option);

        $this->assertTrue($availability['available']);
        $this->assertNull($availability['shipping_time']);
        $this->assertNull($availability['shipping_date']);
    }

    /**
     * Options with items
     */
    public function testWithItem(): void
    {
        $option = $this->createOption([
            [
                'quantity' => 10,
                'required_quantity' => 1,
            ],
        ]);

        $availability = $this->availabilityService->getCalculateOptionAvailability($option);

        $this->assertTrue($availability['available']);
        $this->assertNull($availability['shipping_time']);
        $this->assertNull($availability['shipping_date']);
    }

    // If there's more than one item, option should have higher shipping_time
    public function testWithItemShippingTime(): void
    {
        $option = $this->createOption([
            [
                'quantity' => 1,
                'shipping_time' => 2,
            ],
            [
                'quantity' => 1,
                'shipping_time' => 1,
            ],
        ]);

        $availability = $this->availabilityService->getCalculateOptionAvailability($option);

        $this->assertTrue($availability['available']);
        $this->assertEquals(2, $availability['shipping_time']);
        $this->assertNull($availability['shipping_date']);
    }

    public function testNoEnoughQuantity(): void
    {
        $option = $this->createOption([
            [
                'quantity' => 1,
                'required_quantity' => 2,
            ],
            [
                'quantity' => 5,
                'required_quantity' => 5,
            ],
        ]);

        $availability = $this->availabilityService->getCalculateOptionAvailability($option);

        $this->assertFalse($availability['available']);
        $this->assertNull($availability['shipping_time']);
        $this->assertNull($availability['shipping_date']);
    }

    /**
     * Unlimited items
     */
    public function testUnlimitedShippingTime(): void
    {
        $option = $this->createOption([
            [
                'unlimited_stock_shipping_time' => 1,
                'unlimited_stock_shipping_date' => null,
            ],
        ]);

        $availability = $this->availabilityService->getCalculateOptionAvailability($option);

        $this->assertTrue($availability['available']);
        $this->assertEquals(1, $availability['shipping_time']);
        $this->assertNull($availability['shipping_date']);
    }

    public function testUnlimitedShippingDate(): void
    {
        $option = $this->createOption([
            [
                'unlimited_stock_shipping_time' => null,
                'unlimited_stock_shipping_date' => '2022-09-01',
            ],
        ]);

        $availability = $this->availabilityService->getCalculateOptionAvailability($option);

        $this->assertTrue($availability['available']);
        $this->assertNull($availability['shipping_time']);
        $this->assertTrue($availability['shipping_date']->is('2022-09-01'));
    }

    public function testUnlimitedShippingTimeAndDate(): void
    {
        $option = $this->createOption([
            [
                'unlimited_stock_shipping_time' => 2,
                'unlimited_stock_shipping_date' => '2022-09-01',
            ],
        ]);

        $availability = $this->availabilityService->getCalculateOptionAvailability($option);

        $this->assertTrue($availability['available']);
        $this->assertEquals(2, $availability['shipping_time']);
        $this->assertNull($availability['shipping_date']);
    }

    // If there's more than one item, option should have higher shipping_time
    public function testUnlimitedShippingTimeWithMoreItems(): void
    {
        $option = $this->createOption([
            [
                'unlimited_stock_shipping_time' => 2,
            ],
            [
                'unlimited_stock_shipping_time' => 1,
            ],
            [
                'unlimited_stock_shipping_time' => 3,
            ],
            [
                'unlimited_stock_shipping_time' => 1,
            ],
        ]);

        $availability = $this->availabilityService->getCalculateOptionAvailability($option);

        $this->assertTrue($availability['available']);
        $this->assertEquals(3, $availability['shipping_time']);
        $this->assertNull($availability['shipping_date']);
    }

    // If there's more than one item, option should have higher shipping_date
    public function testUnlimitedShippingDateWithMoreItems(): void
    {
        Carbon::setTestNow(Carbon::create(2022, 9, 1));

        $option = $this->createOption([
            [
                'unlimited_stock_shipping_date' => '2022-09-10',
            ],
            [
                'unlimited_stock_shipping_date' => '2022-09-12',
            ],
            [
                'unlimited_stock_shipping_date' => '2022-09-01',
            ],
            [
                'unlimited_stock_shipping_date' => '2022-09-02',
            ],
        ]);

        $availability = $this->availabilityService->getCalculateOptionAvailability($option);

        $this->assertTrue($availability['available']);
        $this->assertNull($availability['shipping_time']);
        $this->assertTrue($availability['shipping_date']->is('2022-09-12'));
    }
}
