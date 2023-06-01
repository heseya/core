<?php

declare(strict_types=1);

use App\Models\OrderSchema;
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
        DB::table('order_schemas')->lazyById()->each(function (object $orderSchema) {
            $this->insertPrice('price', $orderSchema->price, $orderSchema->id);
            $this->insertPrice('price_initial', $orderSchema->price_initial, $orderSchema->id);
        });

        Schema::table('order_schemas', function (Blueprint $table) {
            $table->dropColumn('price');
            $table->dropColumn('price_initial');
        });
    }

    public function down(): void
    {
        $columnSpec = fn(Blueprint $table, string $name) => $table->float($name, 19, 4);

        Schema::table('order_schemas', function (Blueprint $table) use ($columnSpec) {
            $columnSpec($table, 'price')->nullable();
            $columnSpec($table, 'price_initial')->nullable();
        });

        DB::table('order_schemas')->lazyById()->each(function (object $orderSchema) {
            $data = [
                'price' => $this->getPrice('price', $orderSchema->id),
                'price_initial' => $this->getPrice('price_initial', $orderSchema->id),
            ];

            DB::table('order_schemas')
                ->where('id', $orderSchema->id)
                ->update($data);
        });

        Schema::table('order_schemas', function (Blueprint $table) use ($columnSpec) {
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

    private function insertPrice(string $type, float $value, string $modelId): void {
        DB::table('prices')->insert([
            'id' => Str::uuid(),
            'model_id' => $modelId,
            'model_type' => OrderSchema::class,
            'price_type' => $type,
            'value' => Money::of($value, 'PLN', roundingMode: RoundingMode::HALF_UP)->getMinorAmount(),
        ]);
    }
};
