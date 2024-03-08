<?php

namespace Tests\Unit\Discounts;

use App\Enums\DiscountTargetType;
use App\Models\Discount;
use App\Models\Order;
use App\Models\PriceRange;
use App\Repositories\DiscountRepository;
use App\Services\Contracts\DiscountServiceContract;
use App\Services\ProductService;
use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Money\Exception\UnknownCurrencyException;
use Brick\Money\Money;
use Domain\Currency\Currency;
use Domain\Price\Dtos\PriceDto;
use Domain\ShippingMethod\Models\ShippingMethod;
use Heseya\Dto\DtoException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Tests\TestCase;
use Tests\Utils\FakeDto;

class DiscountApplyCheapestProductTest extends TestCase
{
    use RefreshDatabase;

    private ProductService $productService;
    private DiscountServiceContract $discountService;
    private Currency $currency;
    private DiscountRepository $discountRepository;
    private ShippingMethod $shippingMethod;

    public function setUp(): void
    {
        parent::setUp();

        $this->currency = Currency::DEFAULT;
        $this->productService = App::make(ProductService::class);
        $this->discountRepository = App::make(DiscountRepository::class);
        $this->discountService = App::make(DiscountServiceContract::class);

        $this->shippingMethod = ShippingMethod::factory()->create(['public' => true]);
        $lowRange = PriceRange::query()->create([
            'start' => Money::zero($this->currency->value),
            'value' => Money::of(20.0, $this->currency->value),
        ]);

        $this->shippingMethod->priceRanges()->save($lowRange);
    }

    /**
     * @throws DtoException
     * @throws RoundingNecessaryException
     * @throws UnknownCurrencyException
     * @throws NumberFormatException
     */
    public function testApplyDiscountOnOrderCheapestProductAmount(): void
    {
        /** @var Discount $discount */
        $discount = Discount::factory([
            'target_type' => DiscountTargetType::CHEAPEST_PRODUCT,
            'target_is_allow_list' => false,
            'percentage' => null,
        ])->create();

        $this->discountRepository->setDiscountAmounts($discount->getKey(), [
            PriceDto::from([
                'value' => '50.00',
                'currency' => $this->currency,
            ])
        ]);

        $product1 = $this->productService->create(
            FakeDto::productCreateDto([
                'prices_base' => [PriceDto::from(Money::of(80, $this->currency->value))],
                'public' => true,
            ])
        );

        $product2 = $this->productService->create(
            FakeDto::productCreateDto([
                'prices_base' => [PriceDto::from(Money::of(120, $this->currency->value))],
                'public' => true,
            ])
        );

        /** @var Order $order */
        $order = Order::factory()->create([
            'cart_total_initial' => 600.0,
            'cart_total' => 600.0,
            'shipping_price_initial' => 20.0,
            'shipping_price' => 20.0,
            'shipping_method_id' => $this->shippingMethod->getKey(),
        ]);

        $order->products()->create([
            'product_id' => $product1->getKey(),
            'quantity' => 3,
            'price' => 120.00,
            'price_initial' => 120.00,
            'name' => $product1->name,
            'currency' => $this->currency,
        ]);

        $order->products()->create([
            'product_id' => $product2->getKey(),
            'quantity' => 3,
            'price' => 80.00,
            'price_initial' => 80.00,
            'name' => $product2->name,
            'currency' => $this->currency,
        ]);

        $discountedOrder = $this->discountService->applyDiscountOnOrder($discount, $order);

        $this->assertEquals(550, $discountedOrder->cart_total->getAmount()->toInt()); // 120.0 * 3 + (80 - 50) * 3
    }
}
