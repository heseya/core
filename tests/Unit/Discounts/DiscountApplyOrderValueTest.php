<?php

namespace Tests\Unit\Discounts;

use App\Enums\DiscountTargetType;
use App\Models\Discount;
use App\Models\Order;
use App\Models\PriceRange;
use App\Repositories\DiscountRepository;
use App\Services\Contracts\DiscountServiceContract;
use App\Services\ProductService;
use Brick\Money\Money;
use Domain\Currency\Currency;
use Domain\Price\Dtos\PriceDto;
use Domain\ShippingMethod\Models\ShippingMethod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Tests\TestCase;
use Tests\Utils\FakeDto;

class DiscountApplyOrderValueTest extends TestCase
{
    use RefreshDatabase;

    private DiscountServiceContract $discountService;
    private Currency $currency;
    private DiscountRepository $discountRepository;
    private Order $order;

    public function setUp(): void
    {
        parent::setUp();

        $this->currency = Currency::DEFAULT;
        $productService = App::make(ProductService::class);
        $this->discountRepository = App::make(DiscountRepository::class);
        $this->discountService = App::make(DiscountServiceContract::class);

        $shippingMethod = ShippingMethod::factory()->create(['public' => true]);
        $lowRange = PriceRange::query()->create([
            'start' => Money::zero($this->currency->value),
            'value' => Money::of(20.0, $this->currency->value),
        ]);

        $shippingMethod->priceRanges()->save($lowRange);

        $product = $productService->create(
            FakeDto::productCreateDto([
                'prices_base' => [PriceDto::from(Money::of(120.0, $this->currency->value))],
                'public' => true,
            ])
        );

        $this->order = Order::factory()->create([
            'cart_total_initial' => Money::of(360.0, $this->currency->value),
            'cart_total' => Money::of(360.0, $this->currency->value),
            'shipping_price_initial' => Money::of(20.0, $this->currency->value),
            'shipping_price' => Money::of(20.0, $this->currency->value),
            'shipping_method_id' => $shippingMethod->getKey(),
        ]);

        $this->order->products()->create([
            'product_id' => $product->getKey(),
            'quantity' => 3,
            'price' => Money::of(120.0, $this->currency->value),
            'price_initial' => Money::of(120.0, $this->currency->value),
            'name' => $product->name,
        ]);
    }

    public function testApplyDiscountOnOrderValueAmount(): void
    {
        $discount = Discount::factory([
            'target_type' => DiscountTargetType::ORDER_VALUE,
            'target_is_allow_list' => true,
            'percentage' => null,
        ])->create();

        $this->discountRepository->setDiscountAmounts($discount->getKey(), [
            PriceDto::from([
                'value' => '50.00',
                'currency' => $this->currency,
            ])
        ]);

        $discountedOrder = $this->discountService->applyDiscountOnOrder($discount, $this->order);

        $this->assertEquals(310, $discountedOrder->cart_total->getAmount()->toInt()); // 360 - 50
    }

    public function testApplyDiscountOnOrderValuePercentage(): void
    {
        $discount = Discount::factory([
            'percentage' => '50.00',
            'target_type' => DiscountTargetType::ORDER_VALUE,
            'target_is_allow_list' => true,
        ])->create();

        $discountedOrder = $this->discountService->applyDiscountOnOrder($discount, $this->order);

        $this->assertTrue($discountedOrder->cart_total->isEqualTo(180.0)); // 360 * 50%
    }
}
