<?php

declare(strict_types=1);

namespace Domain\PriceMap\Jobs;

use App\Models\Product;
use App\Services\ProductService;
use Domain\SalesChannel\Models\SalesChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class RefreshCachedPricesForProductAndPriceMaps implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public string $sales_channel_id;

    /**
     * @param array<int,string> $price_map_ids
     */
    public function __construct(public string $product_id, public array $price_map_ids) {}

    /**
     * Execute the job.
     */
    public function handle(
        ProductService $productService,
    ): void {
        $product = Product::findOrFail($this->product_id);
        $salesChannels = SalesChannel::query()->active()->whereIn('price_map_id', $this->price_map_ids)->get();
        $productService->updateMinPrices($product, $salesChannels);
    }
}
