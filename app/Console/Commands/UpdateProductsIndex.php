<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\Contracts\ProductServiceContract;
use Illuminate\Console\Command;

class UpdateProductsIndex extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:update-index {id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update products indexes';

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

        /** @var ProductServiceContract $productService */
        $productService = app(ProductServiceContract::class);

        $bar = $this->output->createProgressBar($count);

        $bar->start();

        /** @var Product $product */
        foreach ($query->cursor() as $product) {
            $productService->updateProductIndex($product);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Done.');
    }
}
