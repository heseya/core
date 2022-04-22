<?php

namespace App\Jobs;

use App\Models\Discount;
use App\Models\Product;
use App\Services\DiscountService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class CheckActiveSales implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     *
     * @param DiscountService $discountService
     *
     * @return void
     */
    public function handle(DiscountService $discountService): void
    {
        $oldActiveSales = Collection::make(Cache::get('sales.active', Collection::make()));

        $products = Collection::make();

        $activeSalesIds = $discountService->activeSales()->pluck('id');
        $sales = $activeSalesIds->diff($oldActiveSales)->merge($oldActiveSales->diff($activeSalesIds));

        $sales = Discount::whereIn('id', $sales)->with(['products', 'productSets', 'productSets.products'])->get();

        foreach ($sales as $sale) {
            $saleProducts = $sale->products;
            foreach ($sale->productSets as $productSet) {
                $saleProducts = $saleProducts->merge($productSet->products);
            }

            if (!$sale->target_is_allow_list) {
                $saleProducts = Product::whereNotIn('id', $saleProducts->pluck('id'))->get();
            }

            $products = $products->merge($saleProducts)->unique('id');
        }

        $discountService->applyDiscountsOnProducts($products);

        Cache::put('sales.active', $activeSalesIds);
    }
}
