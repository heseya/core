<?php

namespace Unit;

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
use App\Repositories\DiscountRepository;
use App\Services\Contracts\DiscountServiceContract;
use App\Services\ProductService;
use Brick\Math\BigDecimal;
use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Money\Exception\UnknownCurrencyException;
use Brick\Money\Money;
use Domain\Currency\Currency;
use Domain\Price\Dtos\PriceDto;
use Domain\ProductSchema\Models\Schema;
use Domain\ProductSchema\Services\OptionService;
use Domain\ProductSchema\Services\SchemaCrudService;
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
    private SchemaCrudService $schemaCrudService;
    private OptionService $optionService;
    private $product;
    private $productToOrderProduct;
    private Schema $schema;
    private $set;
    private $order;
    private $shippingMethod;
    private CartResource $cart;
    private CartItemDto $cartItemDto;
    private CartItemResponse $cartItemResponse;
    private CartResource $cartResource;
    private OrderProductDto $orderProductDto;
    private OrderProductDto $orderProductDtoWithSchemas;
    private $orderProduct;
    private Currency $currency;
    private DiscountRepository $discountRepository;

    public static function discountProductDataProvider(): array
    {
        return [
            /** TODO: REVERT */
            //            'as amount coupon' => [
            //                DiscountType::AMOUNT,
            //                20.0,
            //                110.0,
            //                'coupon',
            //            ],
            'as percentage coupon' => [
                'percentage',
                '20.0',
                104.0,
                'coupon',
            ],
            //            'as amount sale' => [
            //                DiscountType::AMOUNT,
            //                20.0,
            //                110.0,
            //                'sale',
            //            ],
            'as percentage sale' => [
                'percentage',
                '20.0',
                104.0,
                'sale',
            ],
        ];
    }

    public static function discountDataProvider(): array
    {
        return [
            /** TODO: REVERT */
            //            'as amount coupon' => [
            //                DiscountType::AMOUNT,
            //                20.0,
            //                100.0,
            //                'coupon',
            //            ],
            'as percentage coupon' => [
                'percentage',
                '20.0',
                96.0,
                'coupon',
            ],
            //            'as amount sale' => [
            //                DiscountType::AMOUNT,
            //                20.0,
            //                100.0,
            //                'sale',
            //            ],
            'as percentage sale' => [
                'percentage',
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
        $this->schemaCrudService = App::make(SchemaCrudService::class);
        $this->optionService = App::make(OptionService::class);
        $this->discountRepository = App::make(DiscountRepository::class);

        $this->product = $this->productService->create(
            FakeDto::productCreateDto([
                'prices_base' => [PriceDto::from(Money::of(120, $this->currency->value))],
                'public' => true,
            ])
        );

        $this->schema = $this->schemaCrudService->store(
            FakeDto::schemaDto([
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

        $this->cartItemResponse = new CartItemResponse(
            1,
            Money::of(120.0, $this->currency->value),
            Money::of(120.0, $this->currency->value),
            1,
        );

        $this->cart = new CartResource(
            Collection::make([$this->cartItemResponse]),
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
                'prices_base' => [PriceDto::from(Money::of(120, $this->currency->value))],
                'public' => true,
            ])
        );

        $this->orderProduct = OrderProduct::factory()->create([
            'order_id' => $order->getKey(),
            'product_id' => $this->productToOrderProduct->getKey(),
            'quantity' => 1,
            'price' => Money::of(120.0, $this->currency->value),
        ]);

        $this->shippingMethod = ShippingMethod::factory()->create(['public' => true]);
        $lowRange = PriceRange::query()->create([
            'start' => Money::zero($this->currency->value),
            'value' => Money::of(20.0, $this->currency->value),
        ]);

        $this->shippingMethod->priceRanges()->save($lowRange);

        $this->order = Order::factory()->create([
            'cart_total_initial' => Money::of(360.0, $this->currency->value),
            'cart_total' => Money::of(360.0, $this->currency->value),
            'shipping_price_initial' => Money::of(20.0, $this->currency->value),
            'shipping_price' => Money::of(20.0, $this->currency->value),
            'shipping_method_id' => $this->shippingMethod->getKey(),
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

    /**
     * @dataProvider discountProductDataProvider
     */
    public function testApplyDiscountToProduct($type, $value, $result, $discountKind): void
    {
        $this->schema->product()->associate($this->product);
        $this->schema->save();

        $code = $discountKind === 'coupon' ? [] : ['code' => null];

        $discount = Discount::factory(
            [
                $type => $value,
                'target_type' => DiscountTargetType::PRODUCTS,
                'target_is_allow_list' => true,
            ] + $code,
        )->create();

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
    public function testApplyDiscountToProductNotAllowList($type, $value, $result, $discountKind): void
    {
        $this->schema->product()->associate($this->product);
        $this->schema->save();

        $product = $this->productService->create(
            FakeDto::productCreateDto([
                'prices_base' => [PriceDto::from(Money::of(220, $this->currency->value))],
                'public' => true,
            ])
        );

        $code = $discountKind === 'coupon' ? [] : ['code' => null];

        $discount = Discount::factory(
            [
                $type => $value,
                'target_type' => DiscountTargetType::PRODUCTS,
                'target_is_allow_list' => false,
            ] + $code,
        )->create();

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
    public function testApplyDiscountToProductInProductSets($type, $value, $result, $discountKind): void
    {
        $this->schema->product()->associate($this->product);
        $this->schema->save();

        $code = $discountKind === 'coupon' ? [] : ['code' => null];

        $discount = Discount::factory(
            [
                $type => $value,
                'target_type' => DiscountTargetType::PRODUCTS,
                'target_is_allow_list' => true,
            ] + $code,
        )->create();

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
    public function testApplyDiscountToProductInProductSetsNotAllowList($type, $value, $result, $discountKind): void
    {
        $this->schema->product()->associate($this->product);
        $this->schema->save();

        $set = ProductSet::factory()->create([
            'public' => true,
        ]);

        $code = $discountKind === 'coupon' ? [] : ['code' => null];

        $discount = Discount::factory(
            [
                $type => $value,
                'target_type' => DiscountTargetType::PRODUCTS,
                'target_is_allow_list' => false,
            ] + $code,
        )->create();

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
    public function testDiscountNotApplyToProduct($type, $value, $result, $discountKind): void
    {
        $code = $discountKind === 'coupon' ? [] : ['code' => null];

        $discount = Discount::factory(
            [
                $type => $value,
                'target_type' => DiscountTargetType::PRODUCTS,
                'target_is_allow_list' => true,
            ] + $code,
        )->create();

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
    public function testDiscountNotApplyToProductNotAllowList($type, $value, $result, $discountKind): void
    {
        $code = $discountKind === 'coupon' ? [] : ['code' => null];

        $discount = Discount::factory(
            [
                $type => $value,
                'target_type' => DiscountTargetType::PRODUCTS,
                'target_is_allow_list' => false,
            ] + $code,
        )->create();

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
    public function testDiscountNotApplyToProductInProductSets($type, $value, $result, $discountKind): void
    {
        $code = $discountKind === 'coupon' ? [] : ['code' => null];

        $discount = Discount::factory(
            [
                $type => $value,
                'target_type' => DiscountTargetType::PRODUCTS,
                'target_is_allow_list' => true,
            ] + $code,
        )->create();

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
    public function testDiscountNotApplyToProductInProductSetsNotAllowList($type, $value, $result, $discountKind): void
    {
        $code = $discountKind === 'coupon' ? [] : ['code' => null];

        $discount = Discount::factory(
            [
                $type => $value,
                'target_type' => DiscountTargetType::PRODUCTS,
                'target_is_allow_list' => false,
            ] + $code,
        )->create();

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
    public function testApplyDiscountToOrderProduct($type, $value, $result, $discountKind): void
    {
        $code = $discountKind === 'coupon' ? [] : ['code' => null];

        $discount = Discount::factory(
            [
                $type => $value,
                'target_type' => DiscountTargetType::PRODUCTS,
                'target_is_allow_list' => true,
            ] + $code,
        )->create();

        $discount->products()->attach($this->productToOrderProduct);

        $orderProduct = $this->discountService->applyDiscountOnOrderProduct($this->orderProduct, $discount);

        $this->assertTrue($orderProduct->price->isEqualTo($result));
    }

    /**
     * @dataProvider discountDataProvider
     *
     * @throws DtoException
     */
    public function testApplyDiscountToOrderProductNotAllowList($type, $value, $result, $discountKind): void
    {
        $code = $discountKind === 'coupon' ? [] : ['code' => null];
        $product = $this->productService->create(
            FakeDto::productCreateDto([
                'prices_base' => [PriceDto::from(Money::of(220, $this->currency->value))],
                'public' => true,
            ])
        );

        $discount = Discount::factory(
            [
                $type => $value,
                'target_type' => DiscountTargetType::PRODUCTS,
                'target_is_allow_list' => false,
            ] + $code,
        )->create();

        $discount->products()->attach($product);

        $orderProduct = $this->discountService->applyDiscountOnOrderProduct($this->orderProduct, $discount);

        $this->assertTrue($orderProduct->price->isEqualTo($result));
    }

    /**
     * @dataProvider discountDataProvider
     */
    public function testApplyDiscountToOrderProductInProductSets($type, $value, $result, $discountKind): void
    {
        $code = $discountKind === 'coupon' ? [] : ['code' => null];

        $discount = Discount::factory(
            [
                $type => $value,
                'target_type' => DiscountTargetType::PRODUCTS,
                'target_is_allow_list' => true,
            ] + $code,
        )->create();

        $this->productToOrderProduct->sets()->sync([$this->set->getKey()]);

        $discount->productSets()->attach($this->set);

        $orderProduct = $this->discountService->applyDiscountOnOrderProduct($this->orderProduct, $discount);

        $this->assertTrue($orderProduct->price->isEqualTo($result));
    }

    /**
     * @dataProvider discountDataProvider
     */
    public function testApplyDiscountToOrderProductInProductSetsNotAllowList(
        $type,
        $value,
        $result,
        $discountKind,
    ): void {
        $code = $discountKind === 'coupon' ? [] : ['code' => null];
        $set = ProductSet::factory()->create([
            'public' => true,
        ]);

        $discount = Discount::factory(
            [
                $type => $value,
                'target_type' => DiscountTargetType::PRODUCTS,
                'target_is_allow_list' => false,
            ] + $code,
        )->create();

        $this->product->sets()->sync([$this->set->getKey()]);

        $discount->productSets()->attach($set);

        $orderProduct = $this->discountService->applyDiscountOnOrderProduct($this->orderProduct, $discount);

        $this->assertTrue($orderProduct->price->isEqualTo($result));
    }

    /**
     * @dataProvider discountDataProvider
     */
    public function testDiscountNotApplyToOrderProduct($type, $value, $result, $discountKind): void
    {
        $code = $discountKind === 'coupon' ? [] : ['code' => null];

        $discount = Discount::factory(
            [
                $type => $value,
                'target_type' => DiscountTargetType::PRODUCTS,
                'target_is_allow_list' => true,
            ] + $code,
        )->create();

        $orderProduct = $this->discountService->applyDiscountOnOrderProduct($this->orderProduct, $discount);

        $this->assertTrue($orderProduct->price->isEqualTo(120.0));
    }

    /**
     * @dataProvider discountDataProvider
     */
    public function testDiscountNotApplyToOrderProductNotAllowList($type, $value, $result, $discountKind): void
    {
        $code = $discountKind === 'coupon' ? [] : ['code' => null];

        $discount = Discount::factory(
            [
                $type => $value,
                'target_type' => DiscountTargetType::PRODUCTS,
                'target_is_allow_list' => false,
            ] + $code,
        )->create();

        $discount->products()->attach($this->productToOrderProduct);

        $orderProduct = $this->discountService->applyDiscountOnOrderProduct($this->orderProduct, $discount);

        $this->assertTrue($orderProduct->price->isEqualTo(120.0));
    }

    /**
     * @dataProvider discountDataProvider
     */
    public function testDiscountNotApplyToOrderProductInProductSets($type, $value, $result, $discountKind): void
    {
        $code = $discountKind === 'coupon' ? [] : ['code' => null];

        $discount = Discount::factory(
            [
                $type => $value,
                'target_type' => DiscountTargetType::PRODUCTS,
                'target_is_allow_list' => true,
            ] + $code,
        )->create();

        $discount->productSets()->attach($this->set);

        $orderProduct = $this->discountService->applyDiscountOnOrderProduct($this->orderProduct, $discount);

        $this->assertTrue($orderProduct->price->isEqualTo(120.0));
    }

    /**
     * @dataProvider discountDataProvider
     */
    public function testDiscountNotApplyToOrderProductInProductSetsNotAllowList(
        $type,
        $value,
        $result,
        $discountKind,
    ): void {
        $code = $discountKind === 'coupon' ? [] : ['code' => null];

        $discount = Discount::factory(
            [
                $type => $value,
                'target_type' => DiscountTargetType::PRODUCTS,
                'target_is_allow_list' => false,
            ] + $code,
        )->create();

        $this->productToOrderProduct->sets()->sync([$this->set->getKey()]);

        $discount->productSets()->attach($this->set);

        $orderProduct = $this->discountService->applyDiscountOnOrderProduct($this->orderProduct, $discount);

        $this->assertTrue($orderProduct->price->isEqualTo(120.0));
    }

    /**
     * @dataProvider discountDataProvider
     */
    public function testApplyDiscountToCartItem($type, $value, $result, $discountKind): void
    {
        $code = $discountKind === 'coupon' ? [] : ['code' => null];

        $discount = Discount::factory(
            [
                $type => $value,
                'target_type' => DiscountTargetType::PRODUCTS,
                'target_is_allow_list' => true,
            ] + $code,
        )->create();

        $discount->products()->attach($this->product);

        $cartItemResponse = $this->discountService->applyDiscountOnCartItem($discount, $this->cartItemDto, $this->cart);

        $this->assertTrue($cartItemResponse->price_discounted->isEqualTo($result));
    }

    /**
     * @dataProvider discountDataProvider
     *
     * @throws DtoException
     */
    public function testApplyDiscountToCartItemNotAllowList($type, $value, $result, $discountKind): void
    {
        $code = $discountKind === 'coupon' ? [] : ['code' => null];
        $product = $this->productService->create(
            FakeDto::productCreateDto([
                'prices_base' => [PriceDto::from(Money::of(220, $this->currency->value))],
                'public' => true,
            ])
        );

        $discount = Discount::factory(
            [
                $type => $value,
                'target_type' => DiscountTargetType::PRODUCTS,
                'target_is_allow_list' => false,
            ] + $code,
        )->create();

        $discount->products()->attach($product);

        $cartItemResponse = $this->discountService->applyDiscountOnCartItem($discount, $this->cartItemDto, $this->cart);

        $this->assertTrue($cartItemResponse->price_discounted->isEqualTo($result));
    }

    /**
     * @dataProvider discountDataProvider
     */
    public function testApplyDiscountToCartItemInProductSets($type, $value, $result, $discountKind): void
    {
        $code = $discountKind === 'coupon' ? [] : ['code' => null];

        $discount = Discount::factory(
            [
                $type => $value,
                'target_type' => DiscountTargetType::PRODUCTS,
                'target_is_allow_list' => true,
            ] + $code,
        )->create();

        $this->product->sets()->sync([$this->set->getKey()]);

        $discount->productSets()->attach($this->set);

        $cartItemResponse = $this->discountService->applyDiscountOnCartItem($discount, $this->cartItemDto, $this->cart);

        $this->assertTrue($cartItemResponse->price_discounted->isEqualTo($result));
    }

    /**
     * @dataProvider discountDataProvider
     */
    public function testApplyDiscountToCartItemInProductSetsNotAllowList($type, $value, $result, $discountKind): void
    {
        $code = $discountKind === 'coupon' ? [] : ['code' => null];
        $set = ProductSet::factory()->create([
            'public' => true,
        ]);

        $discount = Discount::factory(
            [
                $type => $value,
                'target_type' => DiscountTargetType::PRODUCTS,
                'target_is_allow_list' => false,
            ] + $code,
        )->create();

        $this->product->sets()->sync([$this->set->getKey()]);

        $discount->productSets()->attach($set);

        $cartItemResponse = $this->discountService->applyDiscountOnCartItem($discount, $this->cartItemDto, $this->cart);

        $this->assertTrue($cartItemResponse->price_discounted->isEqualTo($result));
    }

    /**
     * @dataProvider discountDataProvider
     */
    public function testDiscountNotApplyToCartItem($type, $value, $result, $discountKind): void
    {
        $code = $discountKind === 'coupon' ? [] : ['code' => null];

        $discount = Discount::factory(
            [
                $type => $value,
                'target_type' => DiscountTargetType::PRODUCTS,
                'target_is_allow_list' => true,
            ] + $code,
        )->create();

        $cartItemResponse = $this->discountService->applyDiscountOnCartItem($discount, $this->cartItemDto, $this->cart);

        $this->assertTrue($cartItemResponse->price_discounted->isEqualTo(120.0));
    }

    /**
     * @dataProvider discountDataProvider
     */
    public function testDiscountNotApplyToCartItemNotAllowList($type, $value, $result, $discountKind): void
    {
        $code = $discountKind === 'coupon' ? [] : ['code' => null];

        $discount = Discount::factory(
            [
                $type => $value,
                'target_type' => DiscountTargetType::PRODUCTS,
                'target_is_allow_list' => false,
            ] + $code,
        )->create();

        $discount->products()->attach($this->product);

        $cartItemResponse = $this->discountService->applyDiscountOnCartItem($discount, $this->cartItemDto, $this->cart);

        $this->assertTrue($cartItemResponse->price_discounted->isEqualTo(120.0));
    }

    /**
     * @dataProvider discountDataProvider
     */
    public function testDiscountNotApplyToCartItemInProductSets($type, $value, $result, $discountKind): void
    {
        $code = $discountKind === 'coupon' ? [] : ['code' => null];

        $discount = Discount::factory(
            [
                $type => $value,
                'target_type' => DiscountTargetType::PRODUCTS,
                'target_is_allow_list' => true,
            ] + $code,
        )->create();

        $this->product->sets()->sync([$this->set->getKey()]);

        $cartItemResponse = $this->discountService->applyDiscountOnCartItem($discount, $this->cartItemDto, $this->cart);

        $this->assertTrue($cartItemResponse->price_discounted->isEqualTo(120.0));
    }

    /**
     * @dataProvider discountDataProvider
     */
    public function testDiscountNotApplyToCartItemInProductSetsNotAllowList($type, $value, $result, $discountKind): void
    {
        $code = $discountKind === 'coupon' ? [] : ['code' => null];

        $discount = Discount::factory(
            [
                $type => $value,
                'target_type' => DiscountTargetType::PRODUCTS,
                'target_is_allow_list' => false,
            ] + $code,
        )->create();

        $this->product->sets()->sync([$this->set->getKey()]);

        $discount->productSets()->attach($this->set);

        $cartItemResponse = $this->discountService->applyDiscountOnCartItem($discount, $this->cartItemDto, $this->cart);

        $this->assertTrue($cartItemResponse->price_discounted->isEqualTo(120.0));
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

    public function testApplyDiscountOnOrderShippingAmount(): void
    {
        $discount = Discount::factory([
            'target_type' => DiscountTargetType::SHIPPING_PRICE,
            'target_is_allow_list' => true,
            'percentage' => null,
        ])->create();

        $this->discountRepository->setDiscountAmounts($discount->getKey(), [
            PriceDto::from([
                'value' => '10.00',
                'currency' => $this->currency,
            ])
        ]);

        $discount->shippingMethods()->attach($this->shippingMethod->getKey());

        $discountedOrder = $this->discountService->applyDiscountOnOrder($discount, $this->order);

        $this->assertEquals(10, $discountedOrder->shipping_price->getAmount()->toInt()); // 20 - 10
    }

    public function testApplyDiscountOnOrderShippingAmountNotAllow(): void
    {
        $discount = Discount::factory([
            'target_type' => DiscountTargetType::SHIPPING_PRICE,
            'target_is_allow_list' => false,
        ])->create();

        $this->discountRepository->setDiscountAmounts($discount->getKey(), [
            PriceDto::from([
                'value' => '10.00',
                'currency' => $this->currency,
            ])
        ]);

        $discount->shippingMethods()->attach($this->shippingMethod->getKey());

        $discountedOrder = $this->discountService->applyDiscountOnOrder($discount, $this->order);

        $this->assertEquals(20, $discountedOrder->shipping_price->getAmount()->toInt());
    }

    public function testApplyDiscountOnOrderShippingPercentage(): void
    {
        $discount = Discount::factory([
            'percentage' => '25.00',
            'target_type' => DiscountTargetType::SHIPPING_PRICE,
            'target_is_allow_list' => true,
        ])->create();

        $discount->shippingMethods()->attach($this->shippingMethod->getKey());

        $discountedOrder = $this->discountService->applyDiscountOnOrder($discount, $this->order);

        $this->assertTrue($discountedOrder->shipping_price->isEqualTo(15.0)); // 20 * 75%
    }

    public function testApplyDiscountOnOrderShippingPercentageNotAllow(): void
    {
        $discount = Discount::factory([
            'percentage' => '25.00',
            'target_type' => DiscountTargetType::SHIPPING_PRICE,
            'target_is_allow_list' => false,
        ])->create();

        $discount->shippingMethods()->attach($this->shippingMethod->getKey());

        $discountedOrder = $this->discountService->applyDiscountOnOrder($discount, $this->order);

        $this->assertTrue($discountedOrder->shipping_price->isEqualTo(20.0));
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

    /**
     * @throws DtoException
     * @throws RoundingNecessaryException
     * @throws UnknownCurrencyException
     * @throws NumberFormatException
     */
    public function testApplyDiscountOnOrderCheapestProductAmount(): void
    {
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
