<?php

namespace App\Console\Commands;

use App\Models\Product;
use Domain\Language\LanguageService;
use Domain\ProductAttribute\Models\Attribute;
use Domain\ProductAttribute\Services\AttributeOptionService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

class ImportSku extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:import-sku {id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import products SKU';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $productId = $this->argument('id');

        $query = Product::query()
            ->whereHas('items', fn (Builder $query) => $query->whereNotNull('sku'))
            ->whereDoesntHave('attributes', fn (Builder $query) => $query->where('slug', '=', 'sku'));
        if ($productId) {
            $query->where('id', '=', $productId);
        }
        $count = $query->count();

        if ($count === 0) {
            $this->info('No products found.');

            return;
        }

        /** @var Attribute|null $attribute */
        $attribute = Attribute::query()->where('slug', '=', 'sku')->first();
        if (!$attribute) {
            $this->info('No SKU attribute found.');

            return;
        }

        /** @var AttributeOptionService $attributeOptionService */
        $attributeOptionService = app(AttributeOptionService::class);
        $locale = app(LanguageService::class)->defaultLanguage()->getKey();

        $bar = $this->output->createProgressBar($count);

        $bar->start();

        /** @var Product $product */
        foreach ($query->cursor() as $product) {
            $attributeOptionService->importSku($attribute, $product, $locale);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Done.');
    }
}
