<?php

namespace Tests\Feature\Products;

use App\Services\ProductService;
use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Money\Exception\UnknownCurrencyException;
use Domain\Currency\Currency;
use Domain\Product\Enums\ProductSalesChannelStatus;
use Domain\Product\Models\ProductSalesChannel;
use Domain\SalesChannel\Models\SalesChannel;
use Heseya\Dto\DtoException;
use Illuminate\Support\Facades\App;
use Tests\TestCase;
use Tests\Utils\FakeDto;

class ProductSalesChannelTest extends TestCase
{
    /**
     * @dataProvider authProvider
     */
    public function testCreateWithSalesChannel(string $user): void
    {
        $sales_channel = SalesChannel::query()->first() ?? SalesChannel::factory()->create();

        $prices = array_map(fn (Currency $currency) => [
            'value' => '100.00',
            'currency' => $currency->value,
        ], Currency::cases());

        $this->{$user}->givePermissionTo('products.add');

        $data = [
            'translations' => [
                $this->lang => [
                    'name' => 'Test',
                ],
            ],
            'published' => [$this->lang],
            'slug' => 'slug',
            'prices_base' => $prices,
            'shipping_digital' => false,
            'sales_channels' => [
                [
                    'id' => $sales_channel->getKey(),
                    'availability_status' => ProductSalesChannelStatus::DISABLED->value,
                ]
            ]
        ];

        $response = $this
            ->actingAs($this->{$user})
            ->json('POST', '/products', $data);

        $response->assertValid()->assertCreated();

        $this->assertDatabaseHas(ProductSalesChannel::class, [
            'product_id' => $response->json('data.id'),
            'sales_channel_id' => $sales_channel->getKey(),
            'availability_status' => ProductSalesChannelStatus::DISABLED->value,
        ]);
    }

    /**
     * @dataProvider authProvider
     *
     * @throws NumberFormatException
     * @throws RoundingNecessaryException
     * @throws UnknownCurrencyException
     * @throws DtoException
     */
    public function testUpdateWithSalesChannel(string $user): void
    {
        $sales_channel = SalesChannel::query()->first() ?? SalesChannel::factory()->create();

        /** @var ProductService $productService */
        $productService = App::make(ProductService::class);
        $product = $productService->create(FakeDto::productCreateDto());

        $this->{$user}->givePermissionTo('products.edit');

        $data =  [
            'sales_channels' => [
                [
                    'id' => $sales_channel->getKey(),
                    'availability_status' => ProductSalesChannelStatus::HIDDEN->value,
                ]
            ]
        ];

        $response = $this
            ->actingAs($this->{$user})
            ->json('PATCH', "/products/id:{$product->getKey()}", $data);

        $response->assertValid()->assertOk();

        $this->assertDatabaseHas(ProductSalesChannel::class, [
            'product_id' => $product->getKey(),
            'sales_channel_id' => $sales_channel->getKey(),
            'availability_status' => ProductSalesChannelStatus::HIDDEN->value,
        ]);
    }
}
