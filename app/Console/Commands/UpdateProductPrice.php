<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\DiscountService;
use Illuminate\Console\Command;

class UpdateProductPrice extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:update-prices {id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update products prices';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $productId = $this->argument('id');

        $query = Product::query();
        if ($productId) {
            $query->where('id', '=', $productId);
        }
        $count = $query->count();

        if ($count === 0) {
            $this->info('No products find.');

            return;
        }

        /** @var DiscountService $discountService */
        $discountService = app(DiscountService::class);

        $bar = $this->output->createProgressBar($count);

        $bar->start();

        $blockListSales = $discountService->getSalesWithBlockList();
        /** @var Product $product */
        foreach ($query->cursor() as $product) {
            $discountService->applyAllDiscountsOnProduct($product, $blockListSales);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Done.');
    }
}
