<?php

namespace Database\Seeders;

use App\Enums\DiscountTargetType;
use App\Exceptions\ClientException;
use App\Exceptions\ServerException;
use App\Exceptions\StoreException;
use App\Models\Discount;
use App\Models\Order;
use App\Models\Product;
use App\Repositories\DiscountRepository;
use App\Services\Contracts\DiscountServiceContract;
use App\Services\DiscountService;
use Brick\Math\Exception\MathException;
use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Money\Exception\MoneyMismatchException;
use Brick\Money\Exception\UnknownCurrencyException;
use Brick\Money\Money;
use Domain\Language\Language;
use Heseya\Dto\DtoException;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
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
     * @throws StoreException
     */
    public function run(): void
    {
        $discounts = Discount::factory()->count(5)->create();
        $discounts = $discounts->merge(Discount::factory(['code' => null])->count(5)->create());

        $language = Language::query()->where('default', false)->firstOrFail()->getKey();
        
        $discounts->each(function ($discount) use ($language) {
            $this->translations($discount, $language);
        });

        /** @var DiscountService $discountService */
        $discountService = App::make(DiscountServiceContract::class);
        $discountService->applyDiscountsOnProducts(Product::all());

        foreach (Order::query()->inRandomOrder()->limit(30)->get() as $order) {
            /** @var Discount $discount */
            $discount = $discounts
                ->whereIn(
                    'target_type',
                    [
                        DiscountTargetType::ORDER_VALUE,
                        DiscountTargetType::SHIPPING_PRICE,
                    ],
                )
                ->random();

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

            $discountAmount = null;
            if ($discount->percentage === null) {
                [$discountAmount] = DiscountRepository::getDiscountAmounts($discount->getKey(), $order->currency);
            }

            $order->discounts()->attach($discount, [
                'name' => $discount->name,
                'amount' => $discountAmount?->value->getMinorAmount(),
                'currency' => $discountAmount?->value->getCurrency()->getCurrencyCode() ?? $order->currency,
                'percentage' => $discount->percentage,
                'target_type' => $discount->target_type,
                'applied' => $appliedDiscount->getMinorAmount(),
            ]);

            $order->update($update + [
                'summary' => $order->cart_total->plus($order->shipping_price),
            ]);
        }
    }

    /**
     * @throws RoundingNecessaryException
     * @throws MoneyMismatchException
     * @throws MathException
     * @throws UnknownCurrencyException
     * @throws NumberFormatException
     * @throws ServerException
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

        $update = [
            $field => $price->minus($appliedDiscount),
        ];

        return [$appliedDiscount, $update];
    }

    private function translations(Discount $discount, string $language): void
    {
        $translation = Discount::factory()->definition();
        $discount->setLocale($language)->fill(Arr::only($translation, ['name', 'description_html', 'description']));
        $discount->fill(['published' => array_merge($discount->published, [$language])]);
        $discount->save();
    }
}
