<?php

namespace Database\Seeders;

use App\Enums\ShippingType;
use App\Models\PaymentMethod;
use App\Models\ShippingMethod;
use Illuminate\Database\Seeder;

class ShippingMethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $this->createShippingMethodsWithType(ShippingType::getValues(), true);
        $this->createShippingMethodsWithType(ShippingType::getValues(), false);
    }

    private function createShippingMethods(int $count, bool $public): void
    {
        ShippingMethod::factory()->count($count)->create(['public' => $public])->each(function (ShippingMethod $shippingMethod): void {
            $this->addPaymentMethods($shippingMethod);
        });
    }

    private function createShippingMethodsWithType(array $types, bool $public): void
    {
        foreach ($types as $type) {
            $shippingMethod = ShippingMethod::factory()->create([
                'public' => $public,
                'shipping_type' => $type,
            ]);
            $this->addPaymentMethods($shippingMethod);
        }
    }

    private function addPaymentMethods(ShippingMethod $shippingMethod): void
    {
        $paymentMethods = PaymentMethod::factory()->count(rand(1, 3))->create();
        $shippingMethod->paymentMethods()->sync($paymentMethods);
        $priceRange = $shippingMethod->priceRanges()->create([
            'start' => 0,
        ]);
        $priceRange->prices()->create([
            'value' => rand(500, 2000) / 100.0,
        ]);
    }
}
