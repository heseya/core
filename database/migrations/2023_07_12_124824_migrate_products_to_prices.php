<?php

declare(strict_types=1);

use App\Models\Product;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Brick\Money\Money;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration {
    public function up(): void
    {
        DB::table('products')->lazyById()->each(function (object $product): void {
            $this->insertPrice('price', $product->price, $product->id);
            $this->insertPrice('price_min', $product->price_min, $product->id);
            $this->insertPrice('price_max', $product->price_max, $product->id);
            $this->insertPrice('price_min_initial', $product->price_min_initial, $product->id);
            $this->insertPrice('price_max_initial', $product->price_max_initial, $product->id);
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn('price');
            $table->dropColumn('price_min');
            $table->dropColumn('price_max');
            $table->dropColumn('price_min_initial');
            $table->dropColumn('price_max_initial');
        });
    }

    public function down(): void
    {
        $priceColumn = fn (Blueprint $table) => $table->float('price', 19, 4);

        Schema::table('products', function (Blueprint $table) use ($priceColumn): void {
            $priceColumn($table)->nullable();

            $table->float('price_min', 19, 4)->nullable();
            $table->float('price_max', 19, 4)->nullable();
            $table->float('price_min_initial', 19, 4)->nullable();
            $table->float('price_max_initial', 19, 4)->nullable();
        });

        DB::table('products')->lazyById()->each(function (object $discount): void {
            $data = [
                'price' => $this->getPrice('price', $discount->id),
                'price_min' => $this->getPrice('price_min', $discount->id),
                'price_max' => $this->getPrice('price_max', $discount->id),
                'price_min_initial' => $this->getPrice('price_min_initial', $discount->id),
                'price_max_initial' => $this->getPrice('price_max_initial', $discount->id),
            ];

            DB::table('products')
                ->where('id', $discount->id)
                ->update($data);
        });

        Schema::table('products', function (Blueprint $table) use ($priceColumn): void {
            $priceColumn($table)->nullable(false)->change();
        });
    }

    private function getPrice(string $type, string $modelId): ?BigDecimal
    {
        $price = DB::table('prices')
            ->where('model_id', $modelId)
            ->where('price_type', $type)
            ->first();

        if ($price === null) {
            return null;
        }

        return Money::of($price->value, 'PLN')->getAmount();
    }

    private function insertPrice(string $type, ?float $value, string $modelId): void
    {
        if ($value === null) {
            return;
        }

        $currency = 'PLN';

        DB::table('prices')->insert([
            'id' => Str::uuid(),
            'model_id' => $modelId,
            'model_type' => Product::class,
            'price_type' => $type,
            'value' => Money::of($value, $currency, roundingMode: RoundingMode::HALF_UP)->getMinorAmount(),
            'currency' => $currency,
        ]);
    }
};
