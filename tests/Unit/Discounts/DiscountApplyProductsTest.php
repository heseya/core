<?php

namespace Tests\Unit\Discounts;

use App\Dtos\CartDto;
use App\Dtos\CartItemDto;
use App\Dtos\OrderProductDto;
use App\Enums\DiscountTargetType;
use App\Enums\DiscountType;
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

class DiscountApplyProductsTest extends TestCase
{
    use RefreshDatabase;

    private ProductService $productService;
    private DiscountServiceContract $discountService;
    private Product $product;
    private Product $productToOrderProduct;
    private Schema $schema;
    private ProductSet $set;
    private Order $order;
    private CartResource $cart;
    private CartItemDto $cartItemDto;
    private OrderProductDto $orderProductDto;
    private OrderProductDto $orderProductDtoWithSchemas;
    private OrderProduct $orderProduct;
    private Currency $currency;
    private DiscountRepository $discountRepository;

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function discountProductDataProvider(): array
    {
        return [
            'as amount coupon' => [
                DiscountType::AMOUNT,
                '20.0',
                110.0,
                'coupon',
            ],
            'as percentage coupon' => [
                DiscountType::PERCENTAGE,
                '20.0',
                104.0,
                'coupon',
            ],
            'as amount sale' => [
                DiscountType::AMOUNT,
                '20.0',
                110.0,
                'sale',
            ],
            'as percentage sale' => [
                DiscountType::PERCENTAGE,
                '20.0',
                104.0,
                'sale',
            ],
        ];
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function discountDataProvider(): array
    {
        return [
            'as amount coupon' => [
                DiscountType::AMOUNT,
                '20.0',
                100.0,
                'coupon',
            ],
            'as percentage coupon' => [
                DiscountType::PERCENTAGE,
                '20.0',
                96.0,
                'coupon',
            ],
            'as amount sale' => [
                DiscountType::AMOUNT,
                '20.0',
                100.0,
                'sale',
            ],
            'as percentage sale' => [
                DiscountType::PERCENTAGE,
                '20.0',
                96.0,
                'sale',
            ],
        ];
    }

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
        $schemaCrudService = App::make(SchemaCrudService::class);
        $this->discountRepository = App::make(DiscountRepository::class);

        $this->product = $this->productService->create(
            FakeDto::productCreateDto([
                'prices_base' => [PriceDto::from(Money::of(120.0, $this->currency->value))],
                'public' => true,
            ])
        );

        $this->schema = $schemaCrudService->store(
            FakeDto::schemaDto([
                'prices' => [PriceDto::from(Money::of(10.0, $this->currency->value))],
                'type' => 'string',
                'hidden' => false,
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

        $this->orderProductDto = OrderProductDto::fromArray([
            'product_id' => $this->product->getKey(),
            'quantity' => 1,
            'schemas' => [],
        ]);

        $this->orderProductDtoWithSchemas = OrderProductDto::fromArray([
            'product_id' => $this->product->getKey(),
            'quantity' => 1,
            'schemas' => [
                $this->schema->getKey() => 'Schema',
            ],
        ]);

        $order = Order::factory()->create();
        $this->productToOrderProduct = $this->productService->create(
            FakeDto::productCreateDto([
                'prices_base' => [PriceDto::from(Money::of(120.0, $this->currency->value))],
                'public' => true,
            ])
        );

        $this->orderProduct = OrderProduct::factory()->create([
            'order_id' => $order->getKey(),
            'product_id' => $this->productToOrderProduct->getKey(),
            'quantity' => 1,
            'price' => Money::of(120.0, $this->currency->value),
        ]);

        $shippingMethod = ShippingMethod::factory()->create(['public' => true]);
        $lowRange = PriceRange::query()->create([
            'start' => Money::zero($this->currency->value),
            'value' => Money::of(20.0, $this->currency->value),
        ]);

        $shippingMethod->priceRanges()->save($lowRange);

        $this->order = Order::factory()->create([
            'cart_total_initial' => Money::of(360.0, $this->currency->value),
            'cart_total' => Money::of(360.0, $this->currency->value),
            'shipping_price_initial' => Money::of(20.0, $this->currency->value),
            'shipping_price' => Money::of(20.0, $this->currency->value),
            'shipping_method_id' => $shippingMethod->getKey(),
        ]);

        $this->order->products()->create([
            'product_id' => $this->product->getKey(),
            'quantity' => 3,
            'price' => Money::of(120.0, $this->currency->value),
            'price_initial' => Money::of(120.0, $this->currency->value),
            'name' => $this->product->name,
        ]);

        $this->discountService = App::make(DiscountServiceContract::class);
    }

    /**
     * @dataProvider discountProductDataProvider
     */
    public function testApplyDiscountToProduct(DiscountType $type, string $value, float $result, string $discountKind): void
    {
        $this->product->schemas()->sync([$this->schema->getKey()]);

        $discount = $this->prepareDiscount($type, $value, $discountKind, true);

        $discount->products()->attach($this->product);

        $orderProduct = $this->discountService->applyDiscountOnProduct(
            $this->product,
            $this->orderProductDtoWithSchemas,
            $discount,
            $this->currency,
        );

        $this->assertTrue($orderProduct->price->isEqualTo($result));
        $this->assertDatabaseMissing('order_products', [
            'product_id' => $this->product->getKey(),
            'quantity' => 1,
        ]);
    }

    /**
     * @dataProvider discountProductDataProvider
     *
     * @throws DtoException
     */
    public function testApplyDiscountToProductNotAllowList(DiscountType $type, string $value, float $result, string $discountKind): void
    {
        $this->product->schemas()->sync([$this->schema->getKey()]);
        $product = $this->productService->create(
            FakeDto::productCreateDto([
                'prices_base' => [PriceDto::from(Money::of(220, $this->currency->value))],
                'public' => true,
            ])
        );

        $discount = $this->prepareDiscount($type, $value, $discountKind, false);

        $discount->products()->attach($product);

        $orderProduct = $this->discountService->applyDiscountOnProduct(
            $this->product,
            $this->orderProductDtoWithSchemas,
            $discount,
            $this->currency,
        );

        $this->assertTrue($orderProduct->price->isEqualTo($result));
        $this->assertDatabaseMissing('order_products', [
            'product_id' => $this->product->getKey(),
            'quantity' => 1,
        ]);
    }

    /**
     * @dataProvider discountProductDataProvider
     */
    public function testApplyDiscountToProductInProductSets(DiscountType $type, string $value, float $result, string $discountKind): void
    {
        $this->product->schemas()->sync([$this->schema->getKey()]);

        $discount = $this->prepareDiscount($type, $value, $discountKind, true);

        $this->product->sets()->sync([$this->set->getKey()]);

        $discount->productSets()->attach($this->set);

        $orderProduct = $this->discountService->applyDiscountOnProduct(
            $this->product,
            $this->orderProductDtoWithSchemas,
            $discount,
            $this->currency,
        );

        $this->assertTrue($orderProduct->price->isEqualTo($result));
        $this->assertDatabaseMissing('order_products', [
            'product_id' => $this->product->getKey(),
            'quantity' => 1,
        ]);
    }

    /**
     * @dataProvider discountProductDataProvider
     */
    public function testApplyDiscountToProductInProductSetsNotAllowList(DiscountType $type, string $value, float $result, string $discountKind): void
    {
        $this->product->schemas()->sync([$this->schema->getKey()]);
        $set = ProductSet::factory()->create([
            'public' => true,
        ]);

        $discount = $this->prepareDiscount($type, $value, $discountKind, false);

        $this->product->sets()->sync([$set->getKey()]);

        $discount->productSets()->attach($this->set);

        $orderProduct = $this->discountService->applyDiscountOnProduct(
            $this->product,
            $this->orderProductDtoWithSchemas,
            $discount,
            $this->currency,
        );

        $this->assertTrue($orderProduct->price->isEqualTo($result));
        $this->assertDatabaseMissing('order_products', [
            'product_id' => $this->product->getKey(),
            'quantity' => 1,
        ]);
    }

    /**
     * @dataProvider discountProductDataProvider
     */
    public function testDiscountNotApplyToProduct(DiscountType $type, string $value, float $result, string $discountKind): void
    {
        $discount = $this->prepareDiscount($type, $value, $discountKind, true);

        $this->product->sets()->sync([$this->set->getKey()]);

        $orderProduct = $this->discountService->applyDiscountOnProduct(
            $this->product,
            $this->orderProductDto,
            $discount,
            $this->currency,
        );

        $this->assertTrue($orderProduct->price->isEqualTo(120.0));
        $this->assertDatabaseMissing('order_products', [
            'product_id' => $this->product->getKey(),
            'quantity' => 1,
        ]);
    }

    /**
     * @dataProvider discountProductDataProvider
     */
    public function testDiscountNotApplyToProductNotAllowList(DiscountType $type, string $value, float $result, string $discountKind): void
    {
        $discount = $this->prepareDiscount($type, $value, $discountKind, false);

        $discount->products()->attach($this->product);

        $this->product->sets()->sync([$this->set->getKey()]);

        $orderProduct = $this->discountService->applyDiscountOnProduct(
            $this->product,
            $this->orderProductDto,
            $discount,
            $this->currency,
        );

        $this->assertTrue($orderProduct->price->isEqualTo(120.0));
        $this->assertDatabaseMissing('order_products', [
            'product_id' => $this->product->getKey(),
            'quantity' => 1,
        ]);
    }

    /**
     * @dataProvider discountProductDataProvider
     */
    public function testDiscountNotApplyToProductInProductSets(DiscountType $type, string $value, float $result, string $discountKind): void
    {
        $discount = $this->prepareDiscount($type, $value, $discountKind, true);

        $orderProduct = $this->discountService->applyDiscountOnProduct(
            $this->product,
            $this->orderProductDto,
            $discount,
            $this->currency,
        );

        $this->assertTrue($orderProduct->price->isEqualTo(120.0));
        $this->assertDatabaseMissing('order_products', [
            'product_id' => $this->product->getKey(),
            'quantity' => 1,
        ]);
    }

    /**
     * @dataProvider discountProductDataProvider
     */
    public function testDiscountNotApplyToProductInProductSetsNotAllowList(DiscountType $type, string $value, float $result, string $discountKind): void
    {
        $discount = $this->prepareDiscount($type, $value, $discountKind, false);

        $this->product->sets()->sync([$this->set->getKey()]);

        $discount->productSets()->attach($this->set);

        $orderProduct = $this->discountService->applyDiscountOnProduct(
            $this->product,
            $this->orderProductDto,
            $discount,
            $this->currency,
        );

        $this->assertTrue($orderProduct->price->isEqualTo(120.0));
        $this->assertDatabaseMissing('order_products', [
            'product_id' => $this->product->getKey(),
            'quantity' => 1,
        ]);
    }

    /**
     * @dataProvider discountDataProvider
     */
    public function testApplyDiscountToOrderProduct(DiscountType $type, string $value, float $result, string $discountKind): void
    {
        $discount = $this->prepareDiscount($type, $value, $discountKind, true);

        $discount->products()->attach($this->productToOrderProduct);

        $orderProduct = $this->discountService->applyDiscountOnOrderProduct($this->orderProduct, $discount);

        $this->assertTrue($orderProduct->price->isEqualTo($result));
    }

    /**
     * @dataProvider discountDataProvider
     *
     * @throws DtoException
     */
    public function testApplyDiscountToOrderProductNotAllowList(DiscountType $type, string $value, float $result, string $discountKind): void
    {
        $product = $this->productService->create(
            FakeDto::productCreateDto([
                'prices_base' => [PriceDto::from(Money::of(220, $this->currency->value))],
                'public' => true,
            ])
        );

        $discount = $this->prepareDiscount($type, $value, $discountKind, false);

        $discount->products()->attach($product);

        $orderProduct = $this->discountService->applyDiscountOnOrderProduct($this->orderProduct, $discount);

        $this->assertTrue($orderProduct->price->isEqualTo($result));
    }

    /**
     * @dataProvider discountDataProvider
     */
    public function testApplyDiscountToOrderProductInProductSets(DiscountType $type, string $value, float $result, string $discountKind): void
    {
        $discount = $this->prepareDiscount($type, $value, $discountKind, true);

        $this->productToOrderProduct->sets()->sync([$this->set->getKey()]);

        $discount->productSets()->attach($this->set);

        $orderProduct = $this->discountService->applyDiscountOnOrderProduct($this->orderProduct, $discount);

        $this->assertTrue($orderProduct->price->isEqualTo($result));
    }

    /**
     * @dataProvider discountDataProvider
     */
    public function testApplyDiscountToOrderProductInProductSetsNotAllowList(
        DiscountType $type,
        string $value,
        float $result,
        string $discountKind,
    ): void {
        $set = ProductSet::factory()->create([
            'public' => true,
        ]);

        $discount = $this->prepareDiscount($type, $value, $discountKind, false);

        $this->product->sets()->sync([$this->set->getKey()]);

        $discount->productSets()->attach($set);

        $orderProduct = $this->discountService->applyDiscountOnOrderProduct($this->orderProduct, $discount);

        $this->assertTrue($orderProduct->price->isEqualTo($result));
    }

    /**
     * @dataProvider discountDataProvider
     */
    public function testDiscountNotApplyToOrderProduct(DiscountType $type, string $value, float $result, string $discountKind): void
    {
        $discount = $this->prepareDiscount($type, $value, $discountKind, true);

        $orderProduct = $this->discountService->applyDiscountOnOrderProduct($this->orderProduct, $discount);

        $this->assertTrue($orderProduct->price->isEqualTo(120.0));
    }

    /**
     * @dataProvider discountDataProvider
     */
    public function testDiscountNotApplyToOrderProductNotAllowList(DiscountType $type, string $value, float $result, string $discountKind): void
    {
        $discount = $this->prepareDiscount($type, $value, $discountKind, false);

        $discount->products()->attach($this->productToOrderProduct);

        $orderProduct = $this->discountService->applyDiscountOnOrderProduct($this->orderProduct, $discount);

        $this->assertTrue($orderProduct->price->isEqualTo(120.0));
    }

    /**
     * @dataProvider discountDataProvider
     */
    public function testDiscountNotApplyToOrderProductInProductSets(DiscountType $type, string $value, float $result, string $discountKind): void
    {
        $discount = $this->prepareDiscount($type, $value, $discountKind, true);

        $discount->productSets()->attach($this->set);

        $orderProduct = $this->discountService->applyDiscountOnOrderProduct($this->orderProduct, $discount);

        $this->assertTrue($orderProduct->price->isEqualTo(120.0));
    }

    /**
     * @dataProvider discountDataProvider
     */
    public function testDiscountNotApplyToOrderProductInProductSetsNotAllowList(
        DiscountType $type,
        string $value,
        float $result,
        string $discountKind,
    ): void {
        $discount = $this->prepareDiscount($type, $value, $discountKind, false);

        $this->productToOrderProduct->sets()->sync([$this->set->getKey()]);

        $discount->productSets()->attach($this->set);

        $orderProduct = $this->discountService->applyDiscountOnOrderProduct($this->orderProduct, $discount);

        $this->assertTrue($orderProduct->price->isEqualTo(120.0));
    }

    /**
     * @dataProvider discountDataProvider
     */
    public function testApplyDiscountToCartItem(DiscountType $type, string $value, float $result, string $discountKind): void
    {
        $discount = $this->prepareDiscount($type, $value, $discountKind, true);

        $discount->products()->attach($this->product);

        $cartItemResponse = $this->discountService->applyDiscountOnCartItem($discount, $this->cartItemDto, $this->cart);

        $this->assertTrue($cartItemResponse->price_discounted->isEqualTo($result));
    }

    /**
     * @dataProvider discountDataProvider
     *
     * @throws DtoException
     */
    public function testApplyDiscountToCartItemNotAllowList(DiscountType $type, string $value, float $result, string $discountKind): void
    {
        $product = $this->productService->create(
            FakeDto::productCreateDto([
                'prices_base' => [PriceDto::from(Money::of(220, $this->currency->value))],
                'public' => true,
            ])
        );

        $discount = $this->prepareDiscount($type, $value, $discountKind, false);

        $discount->products()->attach($product);

        $cartItemResponse = $this->discountService->applyDiscountOnCartItem($discount, $this->cartItemDto, $this->cart);

        $this->assertTrue($cartItemResponse->price_discounted->isEqualTo($result));
    }

    /**
     * @dataProvider discountDataProvider
     */
    public function testApplyDiscountToCartItemInProductSets(DiscountType $type, string $value, float $result, string $discountKind): void
    {
        $discount = $this->prepareDiscount($type, $value, $discountKind, true);

        $this->product->sets()->sync([$this->set->getKey()]);

        $discount->productSets()->attach($this->set);

        $cartItemResponse = $this->discountService->applyDiscountOnCartItem($discount, $this->cartItemDto, $this->cart);

        $this->assertTrue($cartItemResponse->price_discounted->isEqualTo($result));
    }

    /**
     * @dataProvider discountDataProvider
     */
    public function testApplyDiscountToCartItemInProductSetsNotAllowList(DiscountType $type, string $value, float $result, string $discountKind): void
    {
        $set = ProductSet::factory()->create([
            'public' => true,
        ]);

        $discount = $this->prepareDiscount($type, $value, $discountKind, false);

        $this->product->sets()->sync([$this->set->getKey()]);

        $discount->productSets()->attach($set);

        $cartItemResponse = $this->discountService->applyDiscountOnCartItem($discount, $this->cartItemDto, $this->cart);

        $this->assertTrue($cartItemResponse->price_discounted->isEqualTo($result));
    }

    /**
     * @dataProvider discountDataProvider
     */
    public function testDiscountNotApplyToCartItem(DiscountType $type, string $value, float $result, string $discountKind): void
    {
        $discount = $this->prepareDiscount($type, $value, $discountKind, true);

        $cartItemResponse = $this->discountService->applyDiscountOnCartItem($discount, $this->cartItemDto, $this->cart);

        $this->assertTrue($cartItemResponse->price_discounted->isEqualTo(120.0));
    }

    /**
     * @dataProvider discountDataProvider
     */
    public function testDiscountNotApplyToCartItemNotAllowList(DiscountType $type, string $value, float $result, string $discountKind): void
    {
        $discount = $this->prepareDiscount($type, $value, $discountKind, false);

        $discount->products()->attach($this->product);

        $cartItemResponse = $this->discountService->applyDiscountOnCartItem($discount, $this->cartItemDto, $this->cart);

        $this->assertTrue($cartItemResponse->price_discounted->isEqualTo(120.0));
    }

    /**
     * @dataProvider discountDataProvider
     */
    public function testDiscountNotApplyToCartItemInProductSets(DiscountType $type, string $value, float $result, string $discountKind): void
    {
        $discount = $this->prepareDiscount($type, $value, $discountKind, true);

        $this->product->sets()->sync([$this->set->getKey()]);

        $cartItemResponse = $this->discountService->applyDiscountOnCartItem($discount, $this->cartItemDto, $this->cart);

        $this->assertTrue($cartItemResponse->price_discounted->isEqualTo(120.0));
    }

    /**
     * @dataProvider discountDataProvider
     */
    public function testDiscountNotApplyToCartItemInProductSetsNotAllowList(DiscountType $type, string $value, float $result, string $discountKind): void
    {
        $discount = $this->prepareDiscount($type, $value, $discountKind, false);

        $this->product->sets()->sync([$this->set->getKey()]);

        $discount->productSets()->attach($this->set);

        $cartItemResponse = $this->discountService->applyDiscountOnCartItem($discount, $this->cartItemDto, $this->cart);

        $this->assertTrue($cartItemResponse->price_discounted->isEqualTo(120.0));
    }

    public function testApplyDiscountOnOrderProductAmount(): void
    {
        $discount = Discount::factory([
            'target_type' => DiscountTargetType::PRODUCTS,
            'target_is_allow_list' => true,
            'percentage' => null,
        ])->create();

        $this->discountRepository->setDiscountAmounts($discount->getKey(), [
            PriceDto::from([
                'value' => '50.00',
                'currency' => $this->currency,
            ])
        ]);

        $discount->products()->attach($this->product->getKey());

        $discountedOrder = $this->discountService->applyDiscountOnOrder($discount, $this->order);

        $this->assertEquals(210, $discountedOrder->cart_total->getAmount()->toInt()); // (120 - 50) * 3
    }

    public function testApplyDiscountOnOrderProductAmountNotAllow(): void
    {
        $discount = Discount::factory([
            'target_type' => DiscountTargetType::PRODUCTS,
            'target_is_allow_list' => false,
        ])->create();

        $this->discountRepository->setDiscountAmounts($discount->getKey(), [
            PriceDto::from([
                'value' => '50.00',
                'currency' => $this->currency,
            ])
        ]);

        $discount->products()->attach($this->product->getKey());

        $discountedOrder = $this->discountService->applyDiscountOnOrder($discount, $this->order);

        $this->assertEquals(360, $discountedOrder->cart_total->getAmount()->toInt()); // 120.0 * 3
    }

    public function testApplyDiscountOnOrderProductPercentage(): void
    {
        $discount = Discount::factory([
            'percentage' => '50.00',
            'target_type' => DiscountTargetType::PRODUCTS,
            'target_is_allow_list' => true,
        ])->create();

        $discount->products()->attach($this->product->getKey());

        $discountedOrder = $this->discountService->applyDiscountOnOrder($discount, $this->order);

        $this->assertTrue($discountedOrder->cart_total->isEqualTo(180.0)); // 120 * 50 % * 3
    }

    public function testApplyDiscountOnOrderProductPercentageNotAllow(): void
    {
        $discount = Discount::factory([
            'percentage' => '50.00',
            'target_type' => DiscountTargetType::PRODUCTS,
            'target_is_allow_list' => false,
        ])->create();

        $discount->products()->attach($this->product->getKey());

        $discountedOrder = $this->discountService->applyDiscountOnOrder($discount, $this->order);

        $this->assertTrue($discountedOrder->cart_total->isEqualTo(360.0)); // 120.0 * 3
    }

    private function prepareDiscount(DiscountType $type, string $value, string $discountKind, bool $allowList): Discount
    {
        $code = $discountKind === 'coupon' ? [] : ['code' => null];

        /** @var Discount $discount */
        $discount = Discount::factory(
            [
                'percentage' => $type->is(DiscountType::PERCENTAGE) ? $value : null,
                'target_type' => DiscountTargetType::PRODUCTS,
                'target_is_allow_list' => $allowList,
            ] + $code,
        )->create();

        if ($type->is(DiscountType::AMOUNT)) {
            $amounts = array_map(fn (Currency $currency) => PriceDto::fromMoney(
                Money::of($value, $currency->value),
            ), Currency::cases());

            $this->discountRepository::setDiscountAmounts($discount->getKey(), $amounts);
        }

        return $discount;
    }
}
