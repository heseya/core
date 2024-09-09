<?php

declare(strict_types=1);

namespace Domain\PriceMap\Jobs;

use App\Models\Product;
use App\Services\ProductService;
use Domain\SalesChannel\Models\SalesChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

final class RefreshCachedPricesForSalesChannel implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public string $sales_channel_id;

    public function __construct(SalesChannel|string $salesChannel)
    {
        $this->sales_channel_id = $salesChannel instanceof SalesChannel ? $salesChannel->getKey() : $salesChannel;
    }

    /**
     * Execute the job.
     */
    public function handle(
        ProductService $productService,
    ): void {
        /** @var SalesChannel $salesChannel */
        $salesChannel = SalesChannel::query()->findOrFail($this->sales_channel_id);
        /** @var Collection<int,SalesChannel> $salesChannels */
        $salesChannels = collect([$salesChannel]);
        Product::query()->chunkById(
            100,
            function (EloquentCollection $products) use ($productService, $salesChannels): void {
                /** @var EloquentCollection<int,Product> $products */
                foreach ($products as $product) {
                    $productService->updateMinPrices($product, $salesChannels);
                }
            },
        );
    }
}
