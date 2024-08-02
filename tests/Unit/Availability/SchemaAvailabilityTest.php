<?php

namespace Tests\Unit\Availability;

use App\Enums\SchemaType;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class SchemaAvailabilityTest extends TestCase
{
    use AvailabilityUntiles;

    public function testWithoutOptions(): void
    {
        $schema = $this->createSchema([
            'type' => SchemaType::SELECT,
        ]);

        $availability = $this->availabilityService->getCalculateSchemaAvailability($schema);

        $this->assertFalse($availability['available']);
        $this->assertNull($availability['shipping_time']);
        $this->assertNull($availability['shipping_date']);
    }

    public function testOptionsWithoutItems(): void
    {
        $options = [$this->createOption()];

        $schema = $this->createSchema([
            'type' => SchemaType::SELECT,
        ], $options);

        $availability = $this->availabilityService->getCalculateSchemaAvailability($schema);

        $this->assertTrue($availability['available']);
        $this->assertNull($availability['shipping_time']);
        $this->assertNull($availability['shipping_date']);
    }

    public function testOptionsWithShippingTime(): void
    {
        $options = [
            $this->createOption([
                [
                    'quantity' => 1,
                    'shipping_time' => 1,
                ],
            ]),
            $this->createOption([
                [
                    'quantity' => 1,
                    'shipping_time' => 2,
                ],
            ]),
        ];

        $schema = $this->createSchema([
            'type' => SchemaType::SELECT,
        ], $options);

        $availability = $this->availabilityService->getCalculateSchemaAvailability($schema);

        $this->assertTrue($availability['available']);
        $this->assertEquals(1, $availability['shipping_time']);
        $this->assertNull($availability['shipping_date']);
    }

    public function testOptionsWithShippingDate(): void
    {
        Carbon::setTestNow(Carbon::create(2022, 9, 1));

        $options = [
            $this->createOption([
                [
                    'quantity' => 1,
                    'shipping_date' => '2022-10-10',
                ],
            ]),
            $this->createOption([
                [
                    'quantity' => 1,
                    'shipping_date' => '2022-10-20',
                ],
            ]),
        ];

        $schema = $this->createSchema([
            'type' => SchemaType::SELECT,
        ], $options);

        $availability = $this->availabilityService->getCalculateSchemaAvailability($schema);

        $this->assertTrue($availability['available']);
        $this->assertNull($availability['shipping_time']);
        $this->assertTrue($availability['shipping_date']->is('2022-10-10'));
    }
}
