<?php

namespace Tests\Unit\Discounts;

use App\Dtos\CartDto;
use App\Dtos\CartItemDto;
use App\Dtos\OrderProductDto;
use App\Enums\DiscountTargetType;
use App\Models\CartItemResponse;
use App\Models\CartResource;
use App\Models\Discount;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\PriceRange;
use App\Models\Product;
use App\Models\Schema;
use App\Repositories\DiscountRepository;
use App\Services\Contracts\DiscountServiceContract;
use App\Services\OptionService;
use App\Services\ProductService;
use App\Services\SchemaCrudService;
use Brick\Math\BigDecimal;
use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Money\Exception\UnknownCurrencyException;
use Brick\Money\Money;
use Domain\Currency\Currency;
use Domain\Price\Dtos\PriceDto;
use Domain\ProductSet\ProductSet;
use Domain\SalesChannel\Models\SalesChannel;
use Domain\ShippingMethod\Models\ShippingMethod;
use Heseya\Dto\DtoException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Tests\TestCase;
use Tests\Utils\FakeDto;

class DiscountApplyTest extends TestCase
{
    use RefreshDatabase;

    private ProductService $productService;
    private DiscountServiceContract $discountService;
    private Product $product;
    private ProductSet $set;
    private CartResource $cart;
    private CartItemDto $cartItemDto;
    private Currency $currency;
    private DiscountRepository $discountRepository;

    /**
     * @throws UnknownCurrencyException
     * @throws RoundingNecessaryException
     * @throws NumberFormatException
     * @throws DtoException
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->currency = Currency::DEFAULT;
        $this->productService = App::make(ProductService::class);
        $this->discountRepository = App::make(DiscountRepository::class);

        $this->product = $this->productService->create(
            FakeDto::productCreateDto([
                'prices_base' => [PriceDto::from(Money::of(120.0, $this->currency->value))],
                'public' => true,
            ])
        );

        $this->set = ProductSet::factory()->create([
            'public' => true,
        ]);

        $this->cartItemDto = CartItemDto::fromArray([
            'cartitem_id' => 1,
            'product_id' => $this->product->getKey(),
            'quantity' => 1,
            'schemas' => [],
        ]);

        $cartItemResponse = new CartItemResponse(
            '1',
            Money::of(120.0, $this->currency->value),
            Money::of(120.0, $this->currency->value),
            1,
        );

        $this->cart = new CartResource(
            Collection::make([$cartItemResponse]),
            Collection::make([]),
            Collection::make([]),
            Money::of(120.0, $this->currency->value),
            Money::of(120.0, $this->currency->value),
            Money::zero($this->currency->value),
            Money::zero($this->currency->value),
            Money::zero($this->currency->value),
        );

        $this->discountService = App::make(DiscountServiceContract::class);
    }

    /**
     * @throws UnknownCurrencyException
     * @throws DtoException
     * @throws RoundingNecessaryException
     * @throws NumberFormatException
     */
    public function testApplyDiscountsOnCart(): void
    {
        $sale = Discount::factory([
            'target_type' => DiscountTargetType::PRODUCTS,
            'target_is_allow_list' => true,
            'code' => null,
            'percentage' => null,
        ])->create();

        $this->discountRepository->setDiscountAmounts($sale->getKey(), [
            PriceDto::from([
                'value' => '5.00',
                'currency' => $this->currency,
            ])
        ]);

        $product1 = $this->productService->create(
            FakeDto::productCreateDto([
                'prices_base' => [PriceDto::from(Money::of(30, $this->currency->value))],
                'public' => true,
            ])
        );

        $sale->products()->attach($product1);

        $coupon = Discount::factory([
            'target_type' => DiscountTargetType::PRODUCTS,
            'target_is_allow_list' => true,
            'percentage' => null,
        ])->create();

        $this->discountRepository->setDiscountAmounts($coupon->getKey(), [
            PriceDto::from([
                'value' => '10.00',
                'currency' => $this->currency,
            ])
        ]);

        $product2 = $this->productService->create(
            FakeDto::productCreateDto([
                'prices_base' => [PriceDto::from(Money::of(40, $this->currency->value))],
                'public' => true,
            ])
        );

        $coupon->products()->attach($product2);

        $product3 = $this->productService->create(
            FakeDto::productCreateDto([
                'prices_base' => [PriceDto::from(Money::of(50, $this->currency->value))],
                'public' => true,
            ])
        );

        $coupon2 = Discount::factory([
            'percentage' => '10.0',
            'target_type' => DiscountTargetType::ORDER_VALUE,
            'target_is_allow_list' => true,
        ])->create();

        $shippingMethod = ShippingMethod::factory()->create(['public' => true]);
        $lowRange = PriceRange::query()->create([
            'start' => Money::zero($this->currency->value),
            'value' => Money::of(20.0, $this->currency->value),
        ]);

        $shippingMethod->priceRanges()->save($lowRange);

        $coupon3 = Discount::factory([
            'target_type' => DiscountTargetType::CHEAPEST_PRODUCT,
            'target_is_allow_list' => true,
            'percentage' => null,
        ])->create();

        $this->discountRepository->setDiscountAmounts($coupon3->getKey(), [
            PriceDto::from([
                'value' => '10.00',
                'currency' => $this->currency,
            ])
        ]);

        $cartDto = CartDto::fromArray([
            'currency' => $this->currency,
            'sales_channel_id' => SalesChannel::query()->value('id'),
            'items' => [
                [
                    'cartitem_id' => 0,
                    'product_id' => $product1->getKey(),
                    'quantity' => 2,
                    'schemas' => [],
                ],
                [
                    'cartitem_id' => 1,
                    'product_id' => $product2->getKey(),
                    'quantity' => 3,
                    'schemas' => [],
                ],
                [
                    'cartitem_id' => 2,
                    'product_id' => $product3->getKey(),
                    'quantity' => 1,
                    'schemas' => [],
                ],
            ],
            'coupons' => [
                $coupon->code,
                $coupon2->code,
                $coupon3->code,
            ],
            'shipping_method_id' => $shippingMethod->getKey(),
        ]);

        $cartResource = $this
            ->discountService
            ->calcCartDiscounts(
                $cartDto,
                Collection::make([$product1, $product2, $product3]),
                BigDecimal::zero(),
            );

        $this->assertEquals(162, $cartResource->cart_total->getAmount()->toInt());
        $this->assertEquals(182, $cartResource->summary->getAmount()->toInt());
        $this->assertTrue(count($cartResource->sales) === 1);
        $this->assertTrue(count($cartResource->coupons) === 3);
    }

    public function testMinimalProductPrice(): void
    {
        $discount = Discount::factory(
            [
                'target_type' => DiscountTargetType::PRODUCTS,
                'target_is_allow_list' => true,
                'percentage' => null,
            ],
        )->create();

        $this->discountRepository->setDiscountAmounts($discount->getKey(), [
            PriceDto::from([
                'value' => '200.00',
                'currency' => $this->currency,
            ])
        ]);

        $this->product->sets()->sync([$this->set->getKey()]);

        $discount->productSets()->attach($this->set);

        $cartItemResponse = $this->discountService->applyDiscountOnCartItem($discount, $this->cartItemDto, $this->cart);

        $this->assertEquals(0.01, $cartItemResponse->price_discounted->getAmount()->toFloat());
    }
}
