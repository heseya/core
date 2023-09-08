<?php

namespace Tests\Feature;

use App\Enums\ShippingType;
use App\Models\PriceRange;
use App\Models\Product;
use App\Models\ShippingMethod;
use Brick\Money\Money;
use Domain\Currency\Currency;
use Domain\SalesChannel\Models\SalesChannel;
use Domain\SalesChannel\SalesChannelRepository;
use Tests\TestCase;

class OrderDigitalTest extends TestCase
{
    private Product $digitalProduct;
    private Product $physicalProduct;
    private ShippingMethod $digitalShippingMethod;
    private ShippingMethod $physicalShippingMethod;
    private SalesChannel $salesChannel;

    private array $billingAddress;
    private Currency $currency;

    public function setUp(): void
    {
        parent::setUp();

        $this->currency = Currency::DEFAULT;

        $this->salesChannel = app(SalesChannelRepository::class)->getDefault();

        $this->digitalProduct = Product::factory()->create([
            'shipping_digital' => true,
        ]);
        $this->salesChannel->products()->attach($this->digitalProduct);
        $this->digitalShippingMethod = ShippingMethod::factory()->create([
            'shipping_type' => ShippingType::DIGITAL,
        ]);
        $freeRange = PriceRange::query()->create([
            'start' => Money::zero($this->currency->value),
            'value' => Money::zero($this->currency->value),
        ]);
        $this->digitalShippingMethod->priceRanges()->save($freeRange);

        $this->physicalProduct = Product::factory()->create();
        $this->salesChannel->products()->attach($this->physicalProduct);
        $this->physicalShippingMethod = ShippingMethod::factory()->create([
            'shipping_type' => ShippingType::ADDRESS,
        ]);
        $freeRange = PriceRange::query()->create([
            'start' => Money::zero($this->currency->value),
            'value' => Money::zero($this->currency->value),
        ]);
        $this->physicalShippingMethod->priceRanges()->save($freeRange);

        $this->billingAddress = [
            'name' => 'Wojtek Testowy',
            'phone' => '+48123321123',
            'address' => 'Gdańska 89/1',
            'zip' => '12-123',
            'city' => 'Bydgoszcz',
            'country' => 'PL',
        ];
    }

    /**
     * @dataProvider authProvider
     */
    public function testOrderOnlyDigitalProducts($user): void
    {
        $this->{$user}->givePermissionTo(['orders.add']);

        $this
            ->actingAs($this->{$user})
            ->json('POST', '/orders', [
                'currency' => $this->currency,
                'sales_channel_id' => $this->salesChannel->getKey(),
                'email' => 'test@example.com',
                'digital_shipping_method_id' => $this->digitalShippingMethod->getKey(),
                'billing_address' => $this->billingAddress,
                'items' => [
                    [
                        'product_id' => $this->digitalProduct->getKey(),
                        'quantity' => 1,
                    ],
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('data.shipping_method', null)
            ->assertJsonPath('data.digital_shipping_method.id', $this->digitalShippingMethod->getKey());

        $this->assertDatabaseHas('orders', [
            'digital_shipping_method_id' => $this->digitalShippingMethod->getKey(),
            'shipping_method_id' => null,
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testOrderDigitalAndPhysicalProducts($user): void
    {
        $this->{$user}->givePermissionTo(['orders.add']);

        $this
            ->actingAs($this->{$user})
            ->json('POST', '/orders', [
                'currency' => $this->currency,
                'sales_channel_id' => $this->salesChannel->getKey(),
                'email' => 'test@example.com',
                'shipping_method_id' => $this->physicalShippingMethod->getKey(),
                'digital_shipping_method_id' => $this->digitalShippingMethod->getKey(),
                'billing_address' => $this->billingAddress,
                'shipping_place' => $this->billingAddress,
                'items' => [
                    [
                        'product_id' => $this->digitalProduct->getKey(),
                        'quantity' => 1,
                    ],
                    [
                        'product_id' => $this->physicalProduct->getKey(),
                        'quantity' => 1,
                    ],
                ],
            ])
            ->assertCreated()
            ->assertJsonPath('data.shipping_method.id', $this->physicalShippingMethod->getKey())
            ->assertJsonPath('data.digital_shipping_method.id', $this->digitalShippingMethod->getKey());

        $this->assertDatabaseHas('orders', [
            'digital_shipping_method_id' => $this->digitalShippingMethod->getKey(),
            'shipping_method_id' => $this->physicalShippingMethod->getKey(),
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testOrderDigitalMethodWithPhysicalProduct($user): void
    {
        $this->{$user}->givePermissionTo(['orders.add']);

        $this
            ->actingAs($this->{$user})
            ->json('POST', '/orders', [
                'currency' => $this->currency,
                'sales_channel_id' => $this->salesChannel->getKey(),
                'email' => 'test@example.com',
                'digital_shipping_method_id' => $this->digitalShippingMethod->getKey(),
                'billing_address' => $this->billingAddress,
                'items' => [
                    [
                        'product_id' => $this->physicalProduct->getKey(),
                        'quantity' => 1,
                    ],
                ],
            ])
            ->assertUnprocessable();

        $this->assertDatabaseMissing('orders', [
            'digital_shipping_method_id' => $this->digitalShippingMethod->getKey(),
            'shipping_method_id' => null,
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testOrderPhysicalMethodWithDigitalProduct($user): void
    {
        $this->{$user}->givePermissionTo(['orders.add']);

        $this
            ->actingAs($this->{$user})
            ->json('POST', '/orders', [
                'currency' => $this->currency,
                'sales_channel_id' => $this->salesChannel->getKey(),
                'email' => 'test@example.com',
                'shipping_method_id' => $this->physicalShippingMethod->getKey(),
                'billing_address' => $this->billingAddress,
                'shipping_place' => $this->billingAddress,
                'items' => [
                    [
                        'product_id' => $this->digitalProduct->getKey(),
                        'quantity' => 1,
                    ],
                ],
            ])
            ->assertUnprocessable();

        $this->assertDatabaseMissing('orders', [
            'digital_shipping_method_id' => null,
            'shipping_method_id' => $this->physicalShippingMethod->getKey(),
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testOrderPhysicalProductWithBothMethodes($user): void
    {
        $this->{$user}->givePermissionTo(['orders.add']);

        $this
            ->actingAs($this->{$user})
            ->json('POST', '/orders', [
                'currency' => $this->currency,
                'sales_channel_id' => $this->salesChannel->getKey(),
                'email' => 'test@example.com',
                'shipping_method_id' => $this->physicalShippingMethod->getKey(),
                'digital_shipping_method_id' => $this->digitalShippingMethod->getKey(),
                'billing_address' => $this->billingAddress,
                'shipping_place' => $this->billingAddress,
                'items' => [
                    [
                        'product_id' => $this->physicalProduct->getKey(),
                        'quantity' => 1,
                    ],
                ],
            ])
            ->assertUnprocessable();

        $this->assertDatabaseMissing('orders', [
            'digital_shipping_method_id' => $this->digitalShippingMethod->getKey(),
            'shipping_method_id' => $this->physicalShippingMethod->getKey(),
        ]);
    }

    /**
     * @dataProvider authProvider
     */
    public function testOrderDigitalProductWithBothMethodes($user): void
    {
        $this->{$user}->givePermissionTo(['orders.add']);

        $this
            ->actingAs($this->{$user})
            ->json('POST', '/orders', [
                'currency' => $this->currency,
                'sales_channel_id' => $this->salesChannel->getKey(),
                'email' => 'test@example.com',
                'shipping_method_id' => $this->physicalShippingMethod->getKey(),
                'digital_shipping_method_id' => $this->digitalShippingMethod->getKey(),
                'billing_address' => $this->billingAddress,
                'shipping_place' => $this->billingAddress,
                'items' => [
                    [
                        'product_id' => $this->digitalProduct->getKey(),
                        'quantity' => 1,
                    ],
                ],
            ])
            ->assertUnprocessable();

        $this->assertDatabaseMissing('orders', [
            'digital_shipping_method_id' => $this->digitalShippingMethod->getKey(),
            'shipping_method_id' => $this->physicalShippingMethod->getKey(),
        ]);
    }
}
