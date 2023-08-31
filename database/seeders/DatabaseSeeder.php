<?php

namespace Database\Seeders;

use Domain\Language\Language;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\App;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        App::setLocale(Language::query()->where('default', true)->firstOrFail()->getKey());

        $this
            ->call(InitSeeder::class)
            ->call(SalesChannelSeeder::class)
            ->call(ItemSeeder::class)
            ->call(ProductSetSeeder::class)
            ->call(ProductSeeder::class)
            ->call(ShippingMethodSeeder::class)
            ->call(OrderSeeder::class)
            ->call(PageSeeder::class)
            ->call(UserSeeder::class)
            ->call(DiscountSeeder::class)
            ->call(WebHookEventLogSeeder::class)
            ->call(DepositSeeder::class)
            ->call(DefaultTemplateSeeder::class);
    }
}
