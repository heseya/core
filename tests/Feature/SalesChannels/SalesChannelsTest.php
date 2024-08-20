<?php

declare(strict_types=1);

namespace Feature\SalesChannels;

use App\Enums\ShippingType;
use App\Services\ProductService;
use Brick\Money\Money;
use Domain\Currency\Currency;
use Domain\PaymentMethods\Models\PaymentMethod;
use Domain\SalesChannel\Enums\SalesChannelStatus;
use Domain\SalesChannel\Models\SalesChannel;
use Domain\ShippingMethod\Models\ShippingMethod;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;
use Tests\Utils\FakeDto;

final class SalesChannelsTest extends TestCase
{
    /**
     * @dataProvider authProvider
     */
    public function testCartProcess(string $user): void
    {
        $currency = Currency::DEFAULT;

        /** @var SalesChannel $channel */
        $channel = SalesChannel::factory()->create([
            'vat_rate' => '23',
        ]);

        /** @var ShippingMethod $shipping_method */
        $shipping_method = ShippingMethod::factory()->create();
        $shipping_method->priceRanges()->create([
            'start' => Money::of(0, $currency->value),
            'value' => Money::of(10, $currency->value),
        ]);

        /** @var ProductService $productService */
        $productService = app(ProductService::class);

        // @phpstan-ignore-next-line
        $product = $productService->create(
            FakeDto::productCreateDto([
                'public' => true,
                'prices_base' => [
                    [
                        'value' => 10,
                        'currency' => $currency,
                    ],
                    [
                        'value' => 10,
                        'currency' => $currency,
                    ],
                ],
            ])
        );

        $this->{$user}->givePermissionTo('cart.verify');
        $this
            ->actingAs($this->{$user})
            ->json('POST', '/cart/process', [
                'currency' => $currency,
                'sales_channel_id' => $channel->getKey(),
                'shipping_method_id' => $shipping_method->getKey(),
                'items' => [
                    [
                        'cartitem_id' => '1',
                        'product_id' => $product->getKey(),
                        'quantity' => 2,
                    ],
                ],
            ])
            ->assertOk()
            ->assertJsonFragment(['shipping_price' => '10.00']) // shipping price should remain the same
            ->assertJsonFragment(['price_discounted' => '12.30']) // single product price
            ->assertJsonFragment(['cart_total_initial' => '24.60'])
            ->assertJsonFragment(['cart_total' => '24.60'])
            ->assertJsonFragment(['summary' => '34.60']);
    }

    /**
     * @dataProvider authProvider
     */
    public function testOrderCreate(string $user): void
    {
        Mail::fake();

        $currency = Currency::DEFAULT;

        /** @var SalesChannel $channel */
        $channel = SalesChannel::factory()->create([
            'vat_rate' => '23',
            'status' => SalesChannelStatus::PUBLIC,
        ]);

        /** @var ShippingMethod $shipping_method */
        $shipping_method = ShippingMethod::factory()->create([
            'shipping_type' => ShippingType::POINT_EXTERNAL,
        ]);
        $shipping_method->priceRanges()->create([
            'start' => Money::of(0, $currency->value),
            'value' => Money::of(10, $currency->value),
        ]);

        /** @var PaymentMethod $paymentMethod */
        $paymentMethod = PaymentMethod::factory()->create();

        /** @var ProductService $productService */
        $productService = app(ProductService::class);

        // @phpstan-ignore-next-line
        $product = $productService->create(
            FakeDto::productCreateDto([
                'public' => true,
                'prices_base' => [
                    [
                        'value' => 10,
                        'currency' => $currency,
                    ],
                    [
                        'value' => 10,
                        'currency' => $currency,
                    ],
                ],
            ])
        );

        $this->{$user}->givePermissionTo('orders.add');
        $this
            ->actingAs($this->{$user})
            ->json('POST', '/orders', [
                'currency' => $currency,
                'email' => 'test@example.com',
                'sales_channel_id' => $channel->getKey(),
                'shipping_method_id' => $shipping_method->getKey(),
                'shipping_place' => 'GDA12',
                'billing_address' => [
                    'name' => 'Jan Nowak',
                    'phone' => '516516516',
                    'address' => 'Gdańska 12',
                    'zip' => '80-111',
                    'city' => 'Gdańsk',
                    'country' => 'PL',
                ],
                'items' => [
                    [
                        'product_id' => $product->getKey(),
                        'quantity' => 2,
                    ],
                ],
                'payment_method_id' => $paymentMethod->getKey(),
            ])
            ->assertCreated();

        $this->assertDatabaseHas('orders', [
            'shipping_price' => '1000', // shipping price should remain the same
            'summary' => '3460',
            'cart_total' => '2460', // without shipping
            'cart_total_initial' => '2460',
            'payment_method_type' => $paymentMethod->type,
        ]);

        $this->assertDatabaseHas('order_products', [
            'price' => '1230',
            'price_initial' => '1230',
            'base_price' => '1230',
            'base_price_initial' => '1230',
            'quantity' => 2,
            'vat_rate' => '23.00',
        ]);
    }
}
