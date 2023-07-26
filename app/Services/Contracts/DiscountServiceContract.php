<?php

namespace App\Services\Contracts;

use App\Dtos\CartDto;
use App\Dtos\CartItemDto;
use App\Dtos\CartOrderDto;
use App\Dtos\CouponDto;
use App\Dtos\CouponIndexDto;
use App\Dtos\OrderDto;
use App\Dtos\OrderProductDto;
use App\Dtos\ProductPriceDto;
use App\Dtos\SaleDto;
use App\Dtos\SaleIndexDto;
use App\Models\CartItemResponse;
use App\Models\CartResource;
use App\Models\ConditionGroup;
use App\Models\Discount;
use App\Models\DiscountCondition;
use App\Models\Order;
use App\Models\OrderProduct;
use App\Models\Product;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface DiscountServiceContract
{
    public function calc(float $value, Discount $discount): float;

    public function index(CouponIndexDto|SaleIndexDto $dto): LengthAwarePaginator;

    public function store(CouponDto|SaleDto $dto): Discount;

    public function update(Discount $discount, CouponDto|SaleDto $dto): Discount;

    public function destroy(Discount $discount): void;

    public function checkCondition(
        DiscountCondition $condition,
        ?CartOrderDto $dto = null,
        float $cartValue = 0
    ): bool;

    public function checkConditionGroup(ConditionGroup $group, CartOrderDto $dto, float $cartValue): bool;

    public function checkConditionGroups(Discount $discount, CartOrderDto $dto, float $cartValue): bool;

    public function applyDiscountOnProduct(
        Product $product,
        OrderProductDto $orderProductDto,
        Discount $discount
    ): OrderProduct;

    public function applyDiscountsOnProducts(Collection $products): void;

    public function applyDiscountsOnProduct(Product $product): void;

    public function applyDiscountOnOrderProduct(OrderProduct $orderProduct, Discount $discount): OrderProduct;

    public function applyDiscountOnCartItem(
        Discount $discount,
        CartItemDto $cartItem,
        CartResource $cart,
    ): CartItemResponse;

    public function calcCartDiscounts(CartDto $cart, Collection $products): CartResource;

    /**
     * @return ProductPriceDto[]
     */
    public function calcProductsListDiscounts(Collection $products): array;

    public function calcOrderShippingDiscounts(Order $order, OrderDto $orderDto): Order;

    public function calcOrderProductsAndTotalDiscounts(Order $order, OrderDto $orderDto): Order;

    public function applyDiscountOnOrder(Discount $discount, Order $order): Order;

    public function calcAppliedDiscount(float $price, float $appliedDiscount, string $setting): float;

    public function activeSales(): Collection;

    public function checkDiscountHasTimeConditions(Discount $discount): bool;

    public function checkDiscountTimeConditions(Discount $discount): bool;

    public function calculateDiscount(Discount $discount, bool $updated): void;

    public function checkActiveSales(): void;
}
