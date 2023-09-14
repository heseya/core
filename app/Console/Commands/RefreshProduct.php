<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\Contracts\ProductServiceContract;
use Illuminate\Console\Command;

class RefreshProduct extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'refresh:product {id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh product data';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $product = Product::query()
            ->where('id', $this->argument('id'))
            ->first();

        if (!($product instanceof Product)) {
            $this->error('Product not found.');

            return 0;
        }

        $productService = app(ProductServiceContract::class);
        $productService->updateMinMaxPrices($product);

        $this->info('Done.');

        return 1;
    }
}
