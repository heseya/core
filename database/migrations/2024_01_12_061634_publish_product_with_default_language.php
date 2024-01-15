<?php

use App\Models\Product;
use Domain\Language\Language;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        /** @var Language $lang */
        $lang = Language::query()->where('default', '=', true)->first();

        $products = Product::query()
            ->whereNull('published')
            ->orWhere('published', 'like', '[]')
            ->orWhere('published', 'not like', "%{$lang->getKey()}%");

        foreach ($products->cursor() as $product) {
            $product->published = $product->published ? array_merge($product->published, [$lang->getKey()]) : [$lang->getKey()];
            $product->save();
        }
    }

    public function down(): void
    {
    }
};
