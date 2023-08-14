<?php

declare(strict_types=1);

use App\Models\Discount;
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
        Schema::table('discounts', function (Blueprint $table) {
            $table->float('value', 19, 4)->nullable()->change();
        });

        DB::table('discounts')->lazyById()->each(function (object $discount) {
            if ($discount->type === 'percentage') {
                return;
            }

            if ($discount->type !== 'amount') {
                throw new Exception('Unknown discount type: ' . $discount->type);
            }

            $this->insertPrice($discount->value, $discount->id);

            DB::table('discounts')
                ->where('id', $discount->id)
                ->update(['value' => null]);
        });

        Schema::table('discounts', function (Blueprint $table) {
            $table->dropColumn('type');
            $table->renameColumn('value', 'percentage');
        });

        Schema::table('discounts', function (Blueprint $table) {
            $table->decimal('percentage', 7, 4)->nullable()->change();
        });
    }

    public function down(): void
    {
        $valueColumn = fn (Blueprint $table) => $table->float('value', 19, 4);

        Schema::table('discounts', function (Blueprint $table) {
            $table->string('type', 255);
            $table->renameColumn('percentage', 'value');
        });

        Schema::table('discounts', function (Blueprint $table) use ($valueColumn) {
            $valueColumn($table)->nullable()->change();
        });

         DB::table('discounts')->lazyById()->each(function (object $discount) {
            $amountValue = $this->getPrice('value', $discount->id);

            $data = [
                'type' => $amountValue ? 'amount' : 'percentage',
                'value' => $amountValue ?: $discount->value,
            ];

            DB::table('discounts')
                ->where('id', $discount->id)
                ->update($data);

            $this->deletePrice('value', $discount->id);
        });

        Schema::table('discounts', function (Blueprint $table) use ($valueColumn) {
            $valueColumn($table)->nullable(false)->change();
        });
    }

    private function getPrice(string $type, string $modelId): ?BigDecimal
    {
        /** @var object $price */
        $price = DB::table('prices')
            ->where('model_id', $modelId)
            ->where('price_type', $type)
            ->first();

        if ($price === null) {
            return null;
        }

        return Money::of($price->value, 'PLN')->getAmount();
    }

    private function deletePrice(string $type, string $modelId): void
    {
        DB::table('prices')
            ->where('model_id', $modelId)
            ->where('price_type', $type)
            ->delete();
    }

    private function insertPrice(?float $value, string $modelId): void
    {
        if ($value === null) {
            return;
        }

        $currency = 'PLN';

        DB::table('prices')->insert([
            'id' => Str::uuid(),
            'model_id' => $modelId,
            'model_type' => Discount::class,
            'price_type' => 'value',
            'value' => Money::of($value, $currency, roundingMode: RoundingMode::HALF_UP)->getMinorAmount(),
            'currency' => $currency,
        ]);
    }
};
