<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use App\Models\ShippingMethod;
use Illuminate\Database\Seeder;

class
ShippingMethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $this->createShippingMethods(3, true);
        $this->createShippingMethods(2, false);
    }

    private function createShippingMethods(int $count, bool $public): void
    {
        ShippingMethod::factory()->count($count)->create(['public' => $public])->each(function (ShippingMethod $shippingMethod): void {
            $paymentMethods = PaymentMethod::factory()->count(rand(1, 3))->create();
            $shippingMethod->paymentMethods()->sync($paymentMethods);
            $priceRange = $shippingMethod->priceRanges()->create([
                'start' => 0,
            ]);
            $priceRange->prices()->create([
                'value' => rand(500, 2000) / 100.0,
            ]);
        });
    }
}
