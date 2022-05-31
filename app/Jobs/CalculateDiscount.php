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

class CalculateDiscount implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Discount $discount;
    protected bool $updated;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(?Discount $discount = null, ?bool $updated = false)
    {
        $this->discount = $discount;
        $this->updated = $updated;
    }

    /**
     * Execute the job.
     *
     * @param DiscountService $discountService
     *
     * @return void
     */
    public function handle(DiscountService $discountService): void
    {
        // if job is called after update, then calculate discount for all products,
        // because it may change the list of related products or target_is_allow_list value
        if (!$this->updated && $this->discount !== null) {
            $products = $this->discount->products;
            foreach ($this->discount->productSets as $productSet) {
                $products = $products->merge($productSet->products);
            }

            if (!$this->discount->target_is_allow_list) {
                $products = Product::whereNotIn('id', $products->pluck('id'))->get();
            }

            $products = $products->unique('id');
        } else {
            $products = Product::all();
        }

        // If discount has conditions based on time, then must be added or removed from cache
        if ($discountService->checkDiscountHasTimeConditions($this->discount)) {
            /** @var Collection<int, mixed> $activeSales */
            $activeSales = Cache::get('sales.active', Collection::make());

            if ($discountService->checkDiscountTimeConditions($this->discount)) {
                if (!$activeSales->contains($this->discount->getKey())) {
                    $activeSales->push($this->discount->getKey());
                }
            } else {
                if ($activeSales->contains($this->discount->getKey())) {
                    $activeSales = $activeSales->reject(
                        fn ($value, $key) => $value === $this->discount->getKey(),
                    );
                }
            }
            Cache::put('sales.active', $activeSales);
        }

        $discountService->applyDiscountsOnProducts($products);

        // @phpstan-ignore-next-line
        Product::whereIn('id', $products->pluck('id'))->searchable();
    }
}
