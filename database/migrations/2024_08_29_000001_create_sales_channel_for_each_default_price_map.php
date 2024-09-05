<?php

use App\Models\Product;
use App\Services\ProductService;
use Domain\Currency\Currency;
use Domain\PriceMap\PriceMap;
use Domain\SalesChannel\Enums\SalesChannelActivityType;
use Domain\SalesChannel\Enums\SalesChannelStatus;
use Domain\SalesChannel\Models\SalesChannel;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        foreach (Currency::cases() as $case) {
            if ($case !== Currency::DEFAULT) {
                /** @var PriceMap $priceMap */
                $priceMap = PriceMap::find($case->getDefaultPriceMapId());
                if ($priceMap->salesChannels->count() === 0) {
                    SalesChannel::factory()->create([
                        'name' => 'Default channel for ' . $case->value,
                        'price_map_id' => $priceMap->id,
                        'status' => SalesChannelStatus::PRIVATE->value,
                        'activity' => SalesChannelActivityType::ACTIVE->value,
                        'default' => false,
                    ]);
                }
            }
        }

        $productService = app(ProductService::class);
        Product::query()->chunkById(100, fn(Collection $products) => $products->each(fn(Product $product) => $productService->updateMinPrices($product)));
    }

    public function down(): void {}
};
