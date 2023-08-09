<?php

namespace Database\Seeders;

use App\Enums\DiscountTargetType;
use App\Exceptions\ClientException;
use App\Exceptions\ServerException;
use App\Models\Discount;
use App\Models\Order;
use App\Models\Product;
use App\Services\Contracts\DiscountServiceContract;
use App\Services\DiscountService;
use Brick\Math\Exception\MathException;
use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Money\Exception\MoneyMismatchException;
use Brick\Money\Exception\UnknownCurrencyException;
use Brick\Money\Money;
use Domain\Currency\Currency;
use Heseya\Dto\DtoException;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;

class DiscountSeeder extends Seeder
{
    /**
     * @throws RoundingNecessaryException
     * @throws MoneyMismatchException
     * @throws MathException
     * @throws UnknownCurrencyException
     * @throws ClientException
     * @throws NumberFormatException
     * @throws DtoException
     * @throws ServerException
     */
    public function run(): void
    {
        $amount = mt_rand(10, 15);
        $discounts = Discount::factory()->count($amount)->create();
        $discounts = $discounts->merge(Discount::factory(['code' => null])->count(25 - $amount)->create());

        /** @var DiscountService $discountService */
        $discountService = App::make(DiscountServiceContract::class);
        $discountService->applyDiscountsOnProducts(Product::all());

        $currency = Currency::DEFAULT->value;

        foreach (Order::query()->inRandomOrder()->limit(30)->get() as $order) {
            /** @var Discount $discount */
            $discount = $discounts
                ->whereIn(
                    'target_type',
                    [
                        DiscountTargetType::ORDER_VALUE,
                        DiscountTargetType::SHIPPING_PRICE,
                    ]
                )
                ->random();

            $cart_total = Money::of($order->cart_total, $currency);
            $shipping_price = Money::of($order->shipping_price, $currency);

            /**
             * @var Money $appliedDiscount
             * @var array $update
             */
            [$appliedDiscount, $update] = match ($discount->target_type) {
                DiscountTargetType::ORDER_VALUE => $this
                    ->calcOrderDiscounts(
                        'cart_total',
                        'minimal_order_price',
                        $discountService,
                        $cart_total,
                        $discount,
                    ),
                DiscountTargetType::SHIPPING_PRICE => $this
                    ->calcOrderDiscounts(
                        'shipping_price',
                        'minimal_shipping_price',
                        $discountService,
                        $shipping_price,
                        $discount,
                    ),
            };

            // TODO: Change to money with discounts rework
            $order->discounts()->attach($discount, [
                'name' => $discount->name,
                'value' => $discount->value,
                'type' => $discount->type,
                'target_type' => $discount->target_type,
                'applied_discount' => $appliedDiscount->getAmount()->toFloat(),
            ]);

            $order->update($update + [
                'summary' => $order->cart_total + $order->shipping_price,
            ]);
        }
    }

    /**
     * @throws RoundingNecessaryException
     * @throws MoneyMismatchException
     * @throws MathException
     * @throws UnknownCurrencyException
     * @throws ClientException
     * @throws NumberFormatException
     */
    private function calcOrderDiscounts(
        string $field,
        string $minimalPrice,
        DiscountService $discountService,
        Money $price,
        Discount $discount,
    ): array {
        $appliedDiscount = $discountService->calc($price, $discount);
        $appliedDiscount = $discountService->calcAppliedDiscount($price, $appliedDiscount, $minimalPrice);

        // TODO: Change to money with orders rework
        $update = [
            $field => $price->minus($appliedDiscount)->getAmount()->toFloat(),
        ];

        return [$appliedDiscount, $update];
    }
}
