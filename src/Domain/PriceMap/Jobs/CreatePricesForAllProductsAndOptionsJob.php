<?php

declare(strict_types=1);

namespace Domain\PriceMap\Jobs;

use Domain\PriceMap\PriceMap;
use Domain\PriceMap\PriceMapService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

final class CreatePricesForAllProductsAndOptionsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public PriceMap $priceMap) {}

    /**
     * Execute the job.
     */
    public function handle(PriceMapService $priceMapService): void
    {
        $priceMapService->createPricesForAllMissingProductsAndSchemas($this->priceMap);
        $this->priceMap->refresh();
        $this->priceMap->prices_generated = true;
        $this->priceMap->save();
    }
}
