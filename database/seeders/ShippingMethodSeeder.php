<?php

namespace Database\Seeders;

use App\Enums\ShippingType;
use App\Models\PaymentMethod;
use App\Models\ShippingMethod;
use Brick\Math\Exception\NumberFormatException;
use Brick\Math\Exception\RoundingNecessaryException;
use Brick\Money\Exception\UnknownCurrencyException;
use Brick\Money\Money;
use Domain\Currency\Currency;
use Illuminate\Database\Seeder;

class ShippingMethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->createShippingMethodsWithType(ShippingType::cases(), true);
        $this->createShippingMethodsWithType(ShippingType::cases(), false);
    }

    private function createShippingMethodsWithType(array $types, bool $public): void
    {
        foreach ($types as $type) {
            /** @var ShippingMethod $shippingMethod */
            $shippingMethod = ShippingMethod::factory()->create([
                'public' => $public,
                'shipping_type' => $type,
            ]);
            $this->addPaymentMethods($shippingMethod);
        }
    }

    /**
     * @throws UnknownCurrencyException
     * @throws NumberFormatException
     * @throws RoundingNecessaryException
     */
    private function addPaymentMethods(ShippingMethod $shippingMethod): void
    {
        $currency = Currency::DEFAULT->value;
        $paymentMethods = PaymentMethod::factory()->count(mt_rand(1, 3))->create();
        $shippingMethod->paymentMethods()->sync($paymentMethods);
        $shippingMethod->priceRanges()->create([
            'start' => Money::zero($currency),
            'value' => Money::of(mt_rand(500, 2000) / 100.0, $currency),
        ]);
    }
}
