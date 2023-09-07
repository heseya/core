<?php

use App\Models\Product;
use Domain\Product\Enums\ProductSalesChannelStatus;
use Domain\SalesChannel\Models\SalesChannel;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $salesChannels = SalesChannel::query()->get();

        Product::query()->lazyById()->each(function (Product $product) use ($salesChannels) {
            $product->salesChannels()->syncWithPivotValues($salesChannels, [
                'availability_status' => $product->public
                    ? ProductSalesChannelStatus::PUBLIC->value
                    : ProductSalesChannelStatus::HIDDEN->value
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
