<?php

declare(strict_types=1);

use App\Models\OrderDiscount;
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
        Schema::table('order_discounts', function (Blueprint $table) {
            $table->float('value', 19, 4)->nullable()->change();
        });

        DB::table('order_discounts')->lazyById()->each(function (object $discount) {
            match ($discount->type) {
                'amount', 'percentage' => null,
                default => throw new Exception('Unknown order discount type'),
            };

            $this->insertPrice('applied_discount', $discount->applied_discount, $discount->id);

            if ($discount->type !== 'amount') {
                return;
            }

            $this->insertPrice('value', $discount->value, $discount->id);

            DB::table('order_discounts')
                ->where('id', $discount->id)
                ->update(['value' => null]);
        });

        Schema::table('order_discounts', function (Blueprint $table) {
            $table->dropColumn('type');
            $table->dropColumn('applied_discount');

            $table->renameColumn('value', 'percentage');
        });

        Schema::table('order_discounts', function (Blueprint $table) {
            $table->decimal('percentage', 7, 4)->nullable()->change();
        });
    }

    public function down(): void
    {
        $typeColumn = fn(Blueprint $table) => $table->string('type', 255);
        $columnSpec = fn(Blueprint $table, string $name) => $table->float($name, 19, 4);

        Schema::table('order_discounts', function (Blueprint $table) use ($typeColumn, $columnSpec) {
            $typeColumn($table)->nullable();
            $columnSpec($table, 'applied_discount')->nullable();

            $table->renameColumn('percentage', 'value');
        });

        Schema::table('order_discounts', function (Blueprint $table) use ($typeColumn, $columnSpec) {
            $columnSpec($table, 'value')->nullable()->change();
        });

        DB::table('order_discounts')->lazyById()->each(function (object $discount) {
            $value = $this->getPrice('value', $discount->id);
            $appliedDiscount = $this->getPrice('applied_discount', $discount->id);

            $data = [
                'type' => $value ? 'amount' : 'percentage',
                'value' => $value ?: $discount->value,
                'applied_discount' => $appliedDiscount,
            ];

            DB::table('order_discounts')
                ->where('id', $discount->id)
                ->update($data);
        });

        Schema::table('order_discounts', function (Blueprint $table) use ($typeColumn, $columnSpec) {
            $typeColumn($table)->nullable(false)->change();
            $columnSpec($table, 'applied_discount')->nullable(false)->change();

            $columnSpec($table, 'value')->nullable(false)->change();
        });
    }

    private function getPrice(string $type, string $modelId): ?BigDecimal {
        $price = DB::table('prices')
            ->where('model_id', $modelId)
            ->where('price_type', $type)
            ->first();

        if ($price === null) {
            return null;
        }

        return Money::of($price->value, 'PLN')->getAmount();
    }

    private function insertPrice(string $type, ?float $value, string $modelId): void {
        if ($value === null) {
            return;
        }

        DB::table('prices')->insert([
            'id' => Str::uuid(),
            'model_id' => $modelId,
            'model_type' => OrderDiscount::class,
            'price_type' => $type,
            'value' => Money::of($value, 'PLN', roundingMode: RoundingMode::HALF_UP)->getMinorAmount(),
        ]);
    }
};
