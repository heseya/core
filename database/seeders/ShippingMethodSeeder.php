<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Money\Exception\UnknownCurrencyException;
use Brick\Money\Money;
use Domain\Currency\Currency;
use Domain\ShippingMethod\Models\ShippingMethod;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;

class ShippingMethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $paymentMethods = PaymentMethod::factory()->allMethods()->create();
        $this->createShippingMethodsWithType($paymentMethods, true);
        $this->createShippingMethodsWithType($paymentMethods, false);
    }

    /**
     * @var PaymentMethod[]|Collection<int,PaymentMethod> $paymentMethods
     */
    private function createShippingMethodsWithType(Collection|array $paymentMethods, bool $public): void
    {
        /** @var ShippingMethod[] $shippingMethods */
        $shippingMethods = ShippingMethod::factory()->allMethods()->create([
            'public' => $public,
        ]);

        foreach ($shippingMethods as $shippingMethod) {
            $this->addPriceRanges($shippingMethod);
            $shippingMethod->paymentMethods()->sync($paymentMethods);
        }
    }

    /**
     * @throws UnknownCurrencyException
     * @throws NumberFormatException
     * @throws RoundingNecessaryException
     */
    private function addPriceRanges(ShippingMethod $shippingMethod): void
    {
        $price_ranges = array_map(function (Currency $currency) {
            return [
                'start' => Money::zero($currency->value),
                'value' => Money::of(mt_rand(500, 2000) / 100.0, $currency->value),
            ];
        }, Currency::cases());

        $shippingMethod->priceRanges()->createMany($price_ranges);
    }
}
