<?php

declare(strict_types=1);

namespace Feature\SalesChannels;

use App\Models\Product;
use App\Models\ShippingMethod;
use App\Services\ProductService;
use Brick\Money\Money;
use Domain\Currency\Currency;
use Domain\SalesChannel\Models\SalesChannel;
use Tests\TestCase;
use Tests\Utils\FakeDto;

final class SalesChannelsTest extends TestCase
{
    /**
     * @dataProvider authProvider
     */
    public function testCartProcess(string $user): void
    {
        /** @var SalesChannel $channel */
        $channel = SalesChannel::factory()->create([
            'vat_rate' => '23',
        ]);

        /** @var ShippingMethod $shipping_method */
        $shipping_method = ShippingMethod::factory()->create();
        $shipping_method->priceRanges()->create([
            'start' => Money::of(0, 'PLN'),
            'value' => Money::of(10, 'PLN'),
        ]);

        /** @var ProductService $productService */
        $productService = app(ProductService::class);

        // @phpstan-ignore-next-line
        $product = $productService->create(FakeDto::productCreateDto([
            'public' => true,
            'prices_base' => [
                [
                    'value' => 10,
                    'currency' => Currency::PLN,
                ],
                [
                    'value' => 10,
                    'currency' => Currency::EUR,
                ],
            ],
        ]));

        $this->{$user}->givePermissionTo('cart.verify');
        $this
            ->actingAs($this->{$user})
            ->json('POST', '/cart/process', [
                'shipping_method_id' => $shipping_method->getKey(),
                'items' => [
                    [
                        'cartitem_id' => '1',
                        'product_id' => $product->getKey(),
                        'quantity' => 2,
                    ],
                ],
            ], [
                'X-Sales-Channel' => $channel->getKey(),
            ])
            ->assertOk()
            ->assertJsonFragment(['shipping_price' => 10]) // shipping price should remain the same
            ->assertJsonFragment(['price_discounted' => 12.3]) // product price
            ->assertJsonFragment(['summary' => 34.6]);
    }
}
