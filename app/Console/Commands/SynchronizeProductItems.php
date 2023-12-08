<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\Contracts\ItemServiceContract;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

class SynchronizeProductItems extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:sync-items {metadata} {id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize products items';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $productId = $this->argument('id');
        $metadata = $this->argument('metadata');

        $query = Product::query()
            ->whereHas('metadata', fn (Builder $query) => $query->where('name', '=', $metadata))
            ->whereDoesntHave('items');

        if ($productId) {
            $query->where('id', '=', $productId);
        }
        $count = $query->count();

        if ($count === 0) {
            $this->info('No products found.');

            return;
        }

        /** @var ItemServiceContract $itemService */
        $itemService = app(ItemServiceContract::class);

        $bar = $this->output->createProgressBar($count);

        $bar->start();

        /** @var Product $product */
        foreach ($query->cursor() as $product) {
            $itemService->syncProductItems($product, $metadata);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Done.');
    }
}
