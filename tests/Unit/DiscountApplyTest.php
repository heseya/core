<?php

namespace Unit;

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
use App\Models\ProductSet;
use App\Models\Schema;
use App\Models\ShippingMethod;
use App\Services\Contracts\DiscountServiceContract;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Tests\TestCase;

class DiscountApplyTest extends TestCase
{
    use RefreshDatabase;

    private DiscountServiceContract $discountService;
    private $product;
    private $productToOrderProduct;
    private $schema;
    private $set;
    private $order;
    private $shippingMethod;
    private CartItemDto $cartItemDto;
    private CartItemResponse $cartItemResponse;
    private CartResource $cartResource;
    private OrderProductDto $orderProductDto;
    private OrderProductDto $orderProductDtoWithSchemas;
    private $orderProduct;

    public function setUp(): void
    {
        parent::setUp();

        $this->product = Product::factory()->create([
            'price' => 120.0,
            'public' => true,
        ]);

        $this->schema = Schema::factory()->create([
            'type' => 'string',
            'price' => 10,
            'hidden' => false,
        ]);

        $this->set = ProductSet::factory()->create([
            'public' => true,
        ]);

        $this->cartItemDto = CartItemDto::fromArray([
            'cartitem_id' => 1,
            'product_id' => $this->product->getKey(),
            'quantity' => 1,
            'schemas' => [],
        ]);

        $this->cartItemResponse = new CartItemResponse(1, 120.0, 120.0, 1);

        $this->cart = new CartResource(
            Collection::make([$this->cartItemResponse]),
            Collection::make([]),
            Collection::make([]),
            120.0,
            120.0,
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
        $this->productToOrderProduct = Product::factory()->create([
            'public' => true,
            'price' => 120.0,
        ]);

        $this->orderProduct = OrderProduct::factory()->create([
            'order_id' => $order->getKey(),
            'product_id' => $this->productToOrderProduct->getKey(),
            'quantity' => 1,
            'price' => 120.0,
        ]);

        $this->shippingMethod = ShippingMethod::factory()->create(['public' => true]);
        $lowRange = PriceRange::create(['start' => 0]);
        $lowRange->prices()->create(['value' => 20.0]);

        $this->shippingMethod->priceRanges()->save($lowRange);

        $this->order = Order::factory()->create([
            'cart_total_initial' => 360.0,
            'cart_total' => 360.0,
            'shipping_price_initial' => 20.0,
            'shipping_price' => 20.0,
            'shipping_method_id' => $this->shippingMethod->getKey(),
        ]);

        $this->order->products()->create([
            'product_id' => $this->product->getKey(),
            'quantity' => 3,
            'price' => 120.00,
            'price_initial' => 120.00,
            'name' => $this->product->name,
        ]);

        $this->discountService = App::make(DiscountServiceContract::class);
    }

    public function testApplyDiscountsOnCart(): void
    {
        $sale = Discount::factory([
            'type' => DiscountType::AMOUNT,
            'value' => 5.0,
            'target_type' => DiscountTargetType::PRODUCTS,
            'target_is_allow_list' => true,
            'code' => null,
        ])->create();

        $product1 = Product::factory()->create([
            'price' => 30.0,
            'public' => true,
        ]);

        $sale->products()->attach($product1);

        $coupon = Discount::factory([
            'type' => DiscountType::AMOUNT,
            'value' => 10.0,
            'target_type' => DiscountTargetType::PRODUCTS,
            'target_is_allow_list' => true,
        ])->create();

        $product2 = Product::factory()->create([
            'price' => 40.0,
            'public' => true,
        ]);

        $coupon->products()->attach($product2);

        $product3 = Product::factory()->create([
            'price' => 50.0,
            'public' => true,
        ]);

        $coupon2 = Discount::factory([
            'type' => DiscountType::PERCENTAGE,
            'value' => 10.0,
            'target_type' => DiscountTargetType::ORDER_VALUE,
            'target_is_allow_list' => true,
        ])->create();

        $shippingMethod = ShippingMethod::factory()->create(['public' => true]);

        $coupon3 = Discount::factory([
            'type' => DiscountType::AMOUNT,
            'value' => 10.0,
            'target_type' => DiscountTargetType::CHEAPEST_PRODUCT,
            'target_is_allow_list' => true,
        ])->create();

        $cartDto = CartDto::fromArray([
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
            ->calcCartDiscounts($cartDto, Collection::make([$product1, $product2, $product3]));

        $this->assertTrue($cartResource->cart_total === 162.0);
        $this->assertTrue($cartResource->summary === 162.0);
        $this->assertTrue(count($cartResource->sales) === 1);
        $this->assertTrue(count($cartResource->coupons) === 3);
    }

    public function testMinimalProductPrice(): void
    {
        $discount = Discount::factory(
            [
                'type' => DiscountType::AMOUNT,
                'value' => 200.0,
                'target_type' => DiscountTargetType::PRODUCTS,
                'target_is_allow_list' => true,
            ],
        )->create();

        $this->product->sets()->sync([$this->set->getKey()]);

        $discount->productSets()->attach($this->set);

        $cartItemResponse = $this->discountService->applyDiscountOnCartItem($discount, $this->cartItemDto, $this->cart);

        $this->assertTrue($cartItemResponse->price_discounted === 0.0);
    }

    public function discountProductDataProvider(): array
    {
        return [
            'as amount coupon' => [
                DiscountType::AMOUNT,
                20.0,
                110.0,
                'coupon',
            ],
            'as percentage coupon' => [
                DiscountType::PERCENTAGE,
                20.0,
                104.0,
                'coupon',
            ],
            'as amount sale' => [
                DiscountType::AMOUNT,
                20.0,
                110.0,
                'sale',
            ],
            'as percentage sale' => [
                DiscountType::PERCENTAGE,
                20.0,
                104.0,
                'sale',
            ],
        ];
    }

    /**
     * @dataProvider discountProductDataProvider
     */
    public function testApplyDiscountToProduct($type, $value, $result, $discountKind): void
    {
        $this->product->schemas()->sync([$this->schema->getKey()]);

        $code = $discountKind === 'coupon' ? [] : ['code' => null];

        $discount = Discount::factory(
            [
                'type' => $type,
                'value' => $value,
                'target_type' => DiscountTargetType::PRODUCTS,
                'target_is_allow_list' => true,
            ] + $code,
        )->create();

        $discount->products()->attach($this->product);

        $orderProduct = $this->discountService->applyDiscountOnProduct(
            $this->product,
            $this->orderProductDtoWithSchemas,
            $discount
        );

        $this->assertTrue($orderProduct->price === $result);
        $this->assertDatabaseMissing('order_products', [
            'product_id' => $this->product->getKey(),
            'quantity' => 1,
        ]);
    }

    /**
     * @dataProvider discountProductDataProvider
     */
    public function testApplyDiscountToProductNotAllowList($type, $value, $result, $discountKind): void
    {
        $this->product->schemas()->sync([$this->schema->getKey()]);
        $product = Product::factory()->create([
            'price' => 220.0,
            'public' => true,
        ]);

        $code = $discountKind === 'coupon' ? [] : ['code' => null];

        $discount = Discount::factory(
            [
                'type' => $type,
                'value' => $value,
                'target_type' => DiscountTargetType::PRODUCTS,
                'target_is_allow_list' => false,
            ] + $code,
        )->create();

        $discount->products()->attach($product);

        $orderProduct = $this->discountService->applyDiscountOnProduct(
            $this->product,
            $this->orderProductDtoWithSchemas,
            $discount
        );

        $this->assertTrue($orderProduct->price === $result);
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
        $this->product->schemas()->sync([$this->schema->getKey()]);

        $code = $discountKind === 'coupon' ? [] : ['code' => null];

        $discount = Discount::factory(
            [
                'type' => $type,
                'value' => $value,
                'target_type' => DiscountTargetType::PRODUCTS,
                'target_is_allow_list' => true,
            ] + $code,
        )->create();

        $this->product->sets()->sync([$this->set->getKey()]);

        $discount->productSets()->attach($this->set);

        $orderProduct = $this->discountService->applyDiscountOnProduct(
            $this->product,
            $this->orderProductDtoWithSchemas,
            $discount
        );

        $this->assertTrue($orderProduct->price === $result);
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
        $this->product->schemas()->sync([$this->schema->getKey()]);
        $set = ProductSet::factory()->create([
            'public' => true,
        ]);

        $code = $discountKind === 'coupon' ? [] : ['code' => null];

        $discount = Discount::factory(
            [
                'type' => $type,
                'value' => $value,
                'target_type' => DiscountTargetType::PRODUCTS,
                'target_is_allow_list' => false,
            ] + $code,
        )->create();

        $this->product->sets()->sync([$set->getKey()]);

        $discount->productSets()->attach($this->set);

        $orderProduct = $this->discountService->applyDiscountOnProduct(
            $this->product,
            $this->orderProductDtoWithSchemas,
            $discount
        );

        $this->assertTrue($orderProduct->price === $result);
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
                'type' => $type,
                'value' => $value,
                'target_type' => DiscountTargetType::PRODUCTS,
                'target_is_allow_list' => true,
            ] + $code,
        )->create();

        $this->product->sets()->sync([$this->set->getKey()]);

        $orderProduct = $this->discountService->applyDiscountOnProduct(
            $this->product,
            $this->orderProductDto,
            $discount
        );

        $this->assertTrue($orderProduct->price === 120.0);
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
                'type' => $type,
                'value' => $value,
                'target_type' => DiscountTargetType::PRODUCTS,
                'target_is_allow_list' => false,
            ] + $code,
        )->create();

        $discount->products()->attach($this->product);

        $this->product->sets()->sync([$this->set->getKey()]);

        $orderProduct = $this->discountService->applyDiscountOnProduct(
            $this->product,
            $this->orderProductDto,
            $discount
        );

        $this->assertTrue($orderProduct->price === 120.0);
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
                'type' => $type,
                'value' => $value,
                'target_type' => DiscountTargetType::PRODUCTS,
                'target_is_allow_list' => true,
            ] + $code,
        )->create();

        $orderProduct = $this->discountService->applyDiscountOnProduct(
            $this->product,
            $this->orderProductDto,
            $discount
        );

        $this->assertTrue($orderProduct->price === 120.0);
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
                'type' => $type,
                'value' => $value,
                'target_type' => DiscountTargetType::PRODUCTS,
                'target_is_allow_list' => false,
            ] + $code,
        )->create();

        $this->product->sets()->sync([$this->set->getKey()]);

        $discount->productSets()->attach($this->set);

        $orderProduct = $this->discountService->applyDiscountOnProduct(
            $this->product,
            $this->orderProductDto,
            $discount
        );

        $this->assertTrue($orderProduct->price === 120.0);
        $this->assertDatabaseMissing('order_products', [
            'product_id' => $this->product->getKey(),
            'quantity' => 1,
        ]);
    }

    public function discountDataProvider(): array
    {
        return [
            'as amount coupon' => [
                DiscountType::AMOUNT,
                20.0,
                100.0,
                'coupon',
            ],
            'as percentage coupon' => [
                DiscountType::PERCENTAGE,
                20.0,
                96.0,
                'coupon',
            ],
            'as amount sale' => [
                DiscountType::AMOUNT,
                20.0,
                100.0,
                'sale',
            ],
            'as percentage sale' => [
                DiscountType::PERCENTAGE,
                20.0,
                96.0,
                'sale',
            ],
        ];
    }

    /**
     * @dataProvider discountDataProvider
     */
    public function testApplyDiscountToOrderProduct($type, $value, $result, $discountKind): void
    {
        $code = $discountKind === 'coupon' ? [] : ['code' => null];

        $discount = Discount::factory(
            [
                'type' => $type,
                'value' => $value,
                'target_type' => DiscountTargetType::PRODUCTS,
                'target_is_allow_list' => true,
            ] + $code,
        )->create();

        $discount->products()->attach($this->productToOrderProduct);

        $orderProduct = $this->discountService->applyDiscountOnOrderProduct($this->orderProduct, $discount);

        $this->assertTrue($orderProduct->price === $result);
    }

    /**
     * @dataProvider discountDataProvider
     */
    public function testApplyDiscountToOrderProductNotAllowList($type, $value, $result, $discountKind): void
    {
        $code = $discountKind === 'coupon' ? [] : ['code' => null];
        $product = Product::factory()->create([
            'price' => 220.0,
            'public' => true,
        ]);

        $discount = Discount::factory(
            [
                'type' => $type,
                'value' => $value,
                'target_type' => DiscountTargetType::PRODUCTS,
                'target_is_allow_list' => false,
            ] + $code,
        )->create();

        $discount->products()->attach($product);

        $orderProduct = $this->discountService->applyDiscountOnOrderProduct($this->orderProduct, $discount);

        $this->assertTrue($orderProduct->price === $result);
    }

    /**
     * @dataProvider discountDataProvider
     */
    public function testApplyDiscountToOrderProductInProductSets($type, $value, $result, $discountKind): void
    {
        $code = $discountKind === 'coupon' ? [] : ['code' => null];

        $discount = Discount::factory(
            [
                'type' => $type,
                'value' => $value,
                'target_type' => DiscountTargetType::PRODUCTS,
                'target_is_allow_list' => true,
            ] + $code,
        )->create();

        $this->productToOrderProduct->sets()->sync([$this->set->getKey()]);

        $discount->productSets()->attach($this->set);

        $orderProduct = $this->discountService->applyDiscountOnOrderProduct($this->orderProduct, $discount);

        $this->assertTrue($orderProduct->price === $result);
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
                'type' => $type,
                'value' => $value,
                'target_type' => DiscountTargetType::PRODUCTS,
                'target_is_allow_list' => false,
            ] + $code,
        )->create();

        $this->product->sets()->sync([$this->set->getKey()]);

        $discount->productSets()->attach($set);

        $orderProduct = $this->discountService->applyDiscountOnOrderProduct($this->orderProduct, $discount);

        $this->assertTrue($orderProduct->price === $result);
    }

    /**
     * @dataProvider discountDataProvider
     */
    public function testDiscountNotApplyToOrderProduct($type, $value, $result, $discountKind): void
    {
        $code = $discountKind === 'coupon' ? [] : ['code' => null];

        $discount = Discount::factory(
            [
                'type' => $type,
                'value' => $value,
                'target_type' => DiscountTargetType::PRODUCTS,
                'target_is_allow_list' => true,
            ] + $code,
        )->create();

        $orderProduct = $this->discountService->applyDiscountOnOrderProduct($this->orderProduct, $discount);

        $this->assertTrue($orderProduct->price === 120.0);
    }

    /**
     * @dataProvider discountDataProvider
     */
    public function testDiscountNotApplyToOrderProductNotAllowList($type, $value, $result, $discountKind): void
    {
        $code = $discountKind === 'coupon' ? [] : ['code' => null];

        $discount = Discount::factory(
            [
                'type' => $type,
                'value' => $value,
                'target_type' => DiscountTargetType::PRODUCTS,
                'target_is_allow_list' => false,
            ] + $code,
        )->create();

        $discount->products()->attach($this->productToOrderProduct);

        $orderProduct = $this->discountService->applyDiscountOnOrderProduct($this->orderProduct, $discount);

        $this->assertTrue($orderProduct->price === 120.0);
    }

    /**
     * @dataProvider discountDataProvider
     */
    public function testDiscountNotApplyToOrderProductInProductSets($type, $value, $result, $discountKind): void
    {
        $code = $discountKind === 'coupon' ? [] : ['code' => null];

        $discount = Discount::factory(
            [
                'type' => $type,
                'value' => $value,
                'target_type' => DiscountTargetType::PRODUCTS,
                'target_is_allow_list' => true,
            ] + $code,
        )->create();

        $discount->productSets()->attach($this->set);

        $orderProduct = $this->discountService->applyDiscountOnOrderProduct($this->orderProduct, $discount);

        $this->assertTrue($orderProduct->price === 120.0);
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
                'type' => $type,
                'value' => $value,
                'target_type' => DiscountTargetType::PRODUCTS,
                'target_is_allow_list' => false,
            ] + $code,
        )->create();

        $this->productToOrderProduct->sets()->sync([$this->set->getKey()]);

        $discount->productSets()->attach($this->set);

        $orderProduct = $this->discountService->applyDiscountOnOrderProduct($this->orderProduct, $discount);

        $this->assertTrue($orderProduct->price === 120.0);
    }

    /**
     * @dataProvider discountDataProvider
     */
    public function testApplyDiscountToCartItem($type, $value, $result, $discountKind): void
    {
        $code = $discountKind === 'coupon' ? [] : ['code' => null];

        $discount = Discount::factory(
            [
                'type' => $type,
                'value' => $value,
                'target_type' => DiscountTargetType::PRODUCTS,
                'target_is_allow_list' => true,
            ] + $code,
        )->create();

        $discount->products()->attach($this->product);

        $cartItemResponse = $this->discountService->applyDiscountOnCartItem($discount, $this->cartItemDto, $this->cart);

        $this->assertTrue($cartItemResponse->price_discounted === $result);
    }

    /**
     * @dataProvider discountDataProvider
     */
    public function testApplyDiscountToCartItemNotAllowList($type, $value, $result, $discountKind): void
    {
        $code = $discountKind === 'coupon' ? [] : ['code' => null];
        $product = Product::factory()->create([
            'price' => 220.0,
            'public' => true,
        ]);

        $discount = Discount::factory(
            [
                'type' => $type,
                'value' => $value,
                'target_type' => DiscountTargetType::PRODUCTS,
                'target_is_allow_list' => false,
            ] + $code,
        )->create();

        $discount->products()->attach($product);

        $cartItemResponse = $this->discountService->applyDiscountOnCartItem($discount, $this->cartItemDto, $this->cart);

        $this->assertTrue($cartItemResponse->price_discounted === $result);
    }

    /**
     * @dataProvider discountDataProvider
     */
    public function testApplyDiscountToCartItemInProductSets($type, $value, $result, $discountKind): void
    {
        $code = $discountKind === 'coupon' ? [] : ['code' => null];

        $discount = Discount::factory(
            [
                'type' => $type,
                'value' => $value,
                'target_type' => DiscountTargetType::PRODUCTS,
                'target_is_allow_list' => true,
            ] + $code,
        )->create();

        $this->product->sets()->sync([$this->set->getKey()]);

        $discount->productSets()->attach($this->set);

        $cartItemResponse = $this->discountService->applyDiscountOnCartItem($discount, $this->cartItemDto, $this->cart);

        $this->assertTrue($cartItemResponse->price_discounted === $result);
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
                'type' => $type,
                'value' => $value,
                'target_type' => DiscountTargetType::PRODUCTS,
                'target_is_allow_list' => false,
            ] + $code,
        )->create();

        $this->product->sets()->sync([$this->set->getKey()]);

        $discount->productSets()->attach($set);

        $cartItemResponse = $this->discountService->applyDiscountOnCartItem($discount, $this->cartItemDto, $this->cart);

        $this->assertTrue($cartItemResponse->price_discounted === $result);
    }

    /**
     * @dataProvider discountDataProvider
     */
    public function testDiscountNotApplyToCartItem($type, $value, $result, $discountKind): void
    {
        $code = $discountKind === 'coupon' ? [] : ['code' => null];

        $discount = Discount::factory(
            [
                'type' => $type,
                'value' => $value,
                'target_type' => DiscountTargetType::PRODUCTS,
                'target_is_allow_list' => true,
            ] + $code,
        )->create();

        $cartItemResponse = $this->discountService->applyDiscountOnCartItem($discount, $this->cartItemDto, $this->cart);

        $this->assertTrue($cartItemResponse->price_discounted === 120.0);
    }

    /**
     * @dataProvider discountDataProvider
     */
    public function testDiscountNotApplyToCartItemNotAllowList($type, $value, $result, $discountKind): void
    {
        $code = $discountKind === 'coupon' ? [] : ['code' => null];

        $discount = Discount::factory(
            [
                'type' => $type,
                'value' => $value,
                'target_type' => DiscountTargetType::PRODUCTS,
                'target_is_allow_list' => false,
            ] + $code,
        )->create();

        $discount->products()->attach($this->product);

        $cartItemResponse = $this->discountService->applyDiscountOnCartItem($discount, $this->cartItemDto, $this->cart);

        $this->assertTrue($cartItemResponse->price_discounted === 120.0);
    }

    /**
     * @dataProvider discountDataProvider
     */
    public function testDiscountNotApplyToCartItemInProductSets($type, $value, $result, $discountKind): void
    {
        $code = $discountKind === 'coupon' ? [] : ['code' => null];

        $discount = Discount::factory(
            [
                'type' => $type,
                'value' => $value,
                'target_type' => DiscountTargetType::PRODUCTS,
                'target_is_allow_list' => true,
            ] + $code,
        )->create();

        $this->product->sets()->sync([$this->set->getKey()]);

        $cartItemResponse = $this->discountService->applyDiscountOnCartItem($discount, $this->cartItemDto, $this->cart);

        $this->assertTrue($cartItemResponse->price_discounted === 120.0);
    }

    /**
     * @dataProvider discountDataProvider
     */
    public function testDiscountNotApplyToCartItemInProductSetsNotAllowList($type, $value, $result, $discountKind): void
    {
        $code = $discountKind === 'coupon' ? [] : ['code' => null];

        $discount = Discount::factory(
            [
                'type' => $type,
                'value' => $value,
                'target_type' => DiscountTargetType::PRODUCTS,
                'target_is_allow_list' => false,
            ] + $code,
        )->create();

        $this->product->sets()->sync([$this->set->getKey()]);

        $discount->productSets()->attach($this->set);

        $cartItemResponse = $this->discountService->applyDiscountOnCartItem($discount, $this->cartItemDto, $this->cart);

        $this->assertTrue($cartItemResponse->price_discounted === 120.0);
    }

    public function testApplyDiscountOnOrderValueAmount(): void
    {
        $discount = Discount::factory([
            'type' => DiscountType::AMOUNT,
            'value' => 50.00,
            'target_type' => DiscountTargetType::ORDER_VALUE,
            'target_is_allow_list' => true,
        ])->create();

        $discountedOrder = $this->discountService->applyDiscountOnOrder($discount, $this->order);

        $this->assertTrue($discountedOrder->cart_total === 310.0); // 360 - 50
    }

    public function testApplyDiscountOnOrderValuePercentage(): void
    {
        $discount = Discount::factory([
            'type' => DiscountType::PERCENTAGE,
            'value' => 50.00,
            'target_type' => DiscountTargetType::ORDER_VALUE,
            'target_is_allow_list' => true,
        ])->create();

        $discountedOrder = $this->discountService->applyDiscountOnOrder($discount, $this->order);

        $this->assertTrue($discountedOrder->cart_total === 180.0); // 360 * 50%
    }

    public function testApplyDiscountOnOrderShippingAmount(): void
    {
        $discount = Discount::factory([
            'type' => DiscountType::AMOUNT,
            'value' => 10.00,
            'target_type' => DiscountTargetType::SHIPPING_PRICE,
            'target_is_allow_list' => true,
        ])->create();

        $discount->shippingMethods()->attach($this->shippingMethod->getKey());

        $discountedOrder = $this->discountService->applyDiscountOnOrder($discount, $this->order);

        $this->assertTrue($discountedOrder->shipping_price === 10.0); // 20 - 10
    }

    public function testApplyDiscountOnOrderShippingAmountNotAllow(): void
    {
        $discount = Discount::factory([
            'type' => DiscountType::AMOUNT,
            'value' => 10.00,
            'target_type' => DiscountTargetType::SHIPPING_PRICE,
            'target_is_allow_list' => false,
        ])->create();

        $discount->shippingMethods()->attach($this->shippingMethod->getKey());

        $discountedOrder = $this->discountService->applyDiscountOnOrder($discount, $this->order);

        $this->assertTrue($discountedOrder->shipping_price === 20.0);
    }

    public function testApplyDiscountOnOrderShippingPercentage(): void
    {
        $discount = Discount::factory([
            'type' => DiscountType::PERCENTAGE,
            'value' => 25.00,
            'target_type' => DiscountTargetType::SHIPPING_PRICE,
            'target_is_allow_list' => true,
        ])->create();

        $discount->shippingMethods()->attach($this->shippingMethod->getKey());

        $discountedOrder = $this->discountService->applyDiscountOnOrder($discount, $this->order);

        $this->assertTrue($discountedOrder->shipping_price === 15.0); // 20 * 75%
    }

    public function testApplyDiscountOnOrderShippingPercentageNotAllow(): void
    {
        $discount = Discount::factory([
            'type' => DiscountType::PERCENTAGE,
            'value' => 25.00,
            'target_type' => DiscountTargetType::SHIPPING_PRICE,
            'target_is_allow_list' => false,
        ])->create();

        $discount->shippingMethods()->attach($this->shippingMethod->getKey());

        $discountedOrder = $this->discountService->applyDiscountOnOrder($discount, $this->order);

        $this->assertTrue($discountedOrder->shipping_price === 20.0);
    }

    public function testApplyDiscountOnOrderProductAmount(): void
    {
        $discount = Discount::factory([
            'type' => DiscountType::AMOUNT,
            'value' => 50.00,
            'target_type' => DiscountTargetType::PRODUCTS,
            'target_is_allow_list' => true,
        ])->create();

        $discount->products()->attach($this->product->getKey());

        $discountedOrder = $this->discountService->applyDiscountOnOrder($discount, $this->order);

        $this->assertTrue($discountedOrder->cart_total === 210.0); // (120 - 50) * 3
    }

    public function testApplyDiscountOnOrderProductAmountNotAllow(): void
    {
        $discount = Discount::factory([
            'type' => DiscountType::AMOUNT,
            'value' => 50.00,
            'target_type' => DiscountTargetType::PRODUCTS,
            'target_is_allow_list' => false,
        ])->create();

        $discount->products()->attach($this->product->getKey());

        $discountedOrder = $this->discountService->applyDiscountOnOrder($discount, $this->order);

        $this->assertTrue($discountedOrder->cart_total === 360.0); // 120.0 * 3
    }

    public function testApplyDiscountOnOrderProductPercentage(): void
    {
        $discount = Discount::factory([
            'type' => DiscountType::PERCENTAGE,
            'value' => 50.00,
            'target_type' => DiscountTargetType::PRODUCTS,
            'target_is_allow_list' => true,
        ])->create();

        $discount->products()->attach($this->product->getKey());

        $discountedOrder = $this->discountService->applyDiscountOnOrder($discount, $this->order);

        $this->assertTrue($discountedOrder->cart_total === 180.0); // 120 * 50 % * 3
    }

    public function testApplyDiscountOnOrderProductPercentageNotAllow(): void
    {
        $discount = Discount::factory([
            'type' => DiscountType::PERCENTAGE,
            'value' => 50.00,
            'target_type' => DiscountTargetType::PRODUCTS,
            'target_is_allow_list' => false,
        ])->create();

        $discount->products()->attach($this->product->getKey());

        $discountedOrder = $this->discountService->applyDiscountOnOrder($discount, $this->order);

        $this->assertTrue($discountedOrder->cart_total === 360.0); // 120.0 * 3
    }

    public function testApplyDiscountOnOrderCheapestProductAmount(): void
    {
        $discount = Discount::factory([
            'type' => DiscountType::AMOUNT,
            'value' => 50.00,
            'target_type' => DiscountTargetType::CHEAPEST_PRODUCT,
            'target_is_allow_list' => false,
        ])->create();

        $product1 = Product::factory()->create([
            'public' => true,
            'price' => 80.0,
        ]);

        $product2 = Product::factory()->create([
            'public' => true,
            'price' => 120.0,
        ]);

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
        ]);

        $order->products()->create([
            'product_id' => $product2->getKey(),
            'quantity' => 3,
            'price' => 80.00,
            'price_initial' => 80.00,
            'name' => $product2->name,
        ]);

        $discountedOrder = $this->discountService->applyDiscountOnOrder($discount, $order);

        $this->assertTrue($discountedOrder->cart_total === 550.0); // 120.0 * 3 + (80 - 50) * 3
    }
}
