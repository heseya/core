<?php

declare(strict_types=1);

use App\Models\OrderProduct;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Brick\Money\Money;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('order_products')->lazyById()->each(function (object $orderProduct) {
            $this->insertPrice('price', $orderProduct->price, $orderProduct->id);
            $this->insertPrice('price_initial', $orderProduct->price_initial, $orderProduct->id);
            $this->insertPrice('base_price', $orderProduct->base_price, $orderProduct->id);
            $this->insertPrice('base_price_initial', $orderProduct->base_price_initial, $orderProduct->id);
        });

        Schema::table('order_products', function (Blueprint $table) {
            $table->dropColumn('price');
            $table->dropColumn('price_initial');
            $table->dropColumn('base_price');
            $table->dropColumn('base_price_initial');
        });
    }

    public function down(): void
    {
        $columnSpec = fn(Blueprint $table, string $name) => $table->float($name, 19, 4);

        Schema::table('order_products', function (Blueprint $table) use ($columnSpec) {
            $columnSpec($table, 'price')->nullable();
            $columnSpec($table, 'price_initial')->nullable();

            $table->decimal('base_price', 19, 4)->default(0);
            $table->decimal('base_price_initial', 19, 4)->default(0);
        });

        DB::table('order_products')->lazyById()->each(function (object $orderProduct) {
            $data = [
                'price' => $this->getPrice('price', $orderProduct->id),
                'price_initial' => $this->getPrice('price_initial', $orderProduct->id),
                'base_price' => $this->getPrice('base_price', $orderProduct->id),
                'base_price_initial' => $this->getPrice('base_price_initial', $orderProduct->id),
            ];

            DB::table('order_products')
                ->where('id', $orderProduct->id)
                ->update($data);
        });

        Schema::table('order_products', function (Blueprint $table) use ($columnSpec) {
            $columnSpec($table, 'price')->nullable(false)->change();
            $columnSpec($table, 'price_initial')->nullable(false)->change();
        });
    }

    private function getPrice(string $type, string $modelId): BigDecimal {
        $price = DB::table('prices')
            ->where('model_id', $modelId)
            ->where('price_type', $type)
            ->first();

        return Money::of($price->value, 'PLN')->getAmount();
    }

    private function insertPrice(string $type, string|float $value, string $modelId): void {
        DB::table('prices')->insert([
            'id' => Str::uuid(),
            'model_id' => $modelId,
            'model_type' => OrderProduct::class,
            'price_type' => $type,
            'value' => Money::of($value, 'PLN', roundingMode: RoundingMode::HALF_UP)->getMinorAmount(),
        ]);
    }
};
