<?php

use App\Models\Price;
use App\Models\Product;
use Domain\SalesChannel\SalesChannelRepository;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $salesChannel = app(SalesChannelRepository::class)->getDefault();
        Price::query()
            ->whereIn('model_type', [
                Product::class,
                (new Product())->getMorphClass(),
            ])->lazyById()
            ->each(fn (Price $price) => $price->update(['sales_channel_id' => $salesChannel->id]));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
