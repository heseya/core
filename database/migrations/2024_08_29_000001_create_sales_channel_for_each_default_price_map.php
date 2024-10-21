<?php

use Domain\Currency\Currency;
use Domain\Language\Language;
use Domain\PriceMap\PriceMap;
use Domain\SalesChannel\Enums\SalesChannelActivityType;
use Domain\SalesChannel\Enums\SalesChannelStatus;
use Domain\SalesChannel\Models\SalesChannel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        foreach (Currency::cases() as $case) {
            if ($case !== Currency::DEFAULT) {
                /** @var PriceMap $priceMap */
                $priceMap = PriceMap::find($case->getDefaultPriceMapId());
                if ($priceMap->salesChannels->count() === 0) {
                    SalesChannel::create([
                        'name' => 'Default channel for ' . $case->value,
                        'price_map_id' => $priceMap->id,
                        'status' => SalesChannelStatus::PRIVATE->value,
                        'activity' => SalesChannelActivityType::ACTIVE->value,
                        'default' => false,
                        'slug' => Str::slug('Default channel for ' . $case->value),
                        'language_id' => Language::default()->getKey(),
                        'vat_rate' => '0',
                    ]);
                }
            }
        }
    }

    public function down(): void {}
};
