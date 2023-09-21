<?php

namespace Database\Seeders;

use App\Enums\DiscountTargetType;
use App\Models\Discount;
use App\Models\Order;
use App\Models\Product;
use App\Services\Contracts\DiscountServiceContract;
use App\Services\DiscountService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;

class DiscountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $amount = mt_rand(10, 15);
        $discounts = Discount::factory()->count($amount)->create();
        $discounts = $discounts->merge(Discount::factory(['code' => null])->count(25 - $amount)->create());

        /** @var DiscountService $discountService */
        $discountService = App::make(DiscountServiceContract::class);

        $discountService->applyDiscountsOnProducts(Product::all());

        foreach (Order::inRandomOrder()->limit(30)->get() as $order) {
            $discount = $discounts
                ->whereIn(
                    'target_type',
                    [
                        DiscountTargetType::ORDER_VALUE,
                        DiscountTargetType::SHIPPING_PRICE,
                    ],
                )
                ->random();

            $update = [];
            $appliedDiscount = 0;

            [$appliedDiscount, $update] = match ($discount->target_type->value) {
                DiscountTargetType::ORDER_VALUE => $this
                    ->calcOrderDiscounts(
                        'cart_total',
                        'minimal_order_price',
                        $discountService,
                        $order->cart_total,
                        $discount,
                    ),
                DiscountTargetType::SHIPPING_PRICE => $this
                    ->calcOrderDiscounts(
                        'shipping_price',
                        'minimal_shipping_price',
                        $discountService,
                        $order->shipping_price,
                        $discount,
                    ),
            };

            $order->discounts()->attach($discount, [
                'name' => $discount->name,
                'value' => $discount->value,
                'type' => $discount->type,
                'target_type' => $discount->target_type,
                'applied_discount' => $appliedDiscount,
            ]);

            $order->update($update + [
                'summary' => $order->cart_total + $order->shipping_price,
            ]);
        }
    }

    private function calcOrderDiscounts(
        string $field,
        string $minimalPrice,
        DiscountService $discountService,
        float $price,
        Discount $discount,
    ): array {
        $appliedDiscount = $discountService->calc($price, $discount);
        $appliedDiscount = $discountService->calcAppliedDiscount($price, $appliedDiscount, $minimalPrice);

        $update = [
            $field => $price - $appliedDiscount,
        ];

        return [$appliedDiscount, $update];
    }
}
