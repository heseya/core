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
use Ramsey\Uuid\Uuid;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('order_discounts', function (Blueprint $table) {
            $table->uuid('id')->nullable();
            $table->float('value', 19, 4)->nullable()->change();
        });

        DB::table('order_discounts')->orderBy('discount_id')->lazy()->each(function (object $orderDiscount) {
            $id = Uuid::uuid4()->toString();

            DB::table('order_discounts')
                ->where('discount_id', $orderDiscount->discount_id)
                ->where('model_id', $orderDiscount->model_id)
                ->update(['id' => $id]);

            if ($orderDiscount->type === 'percentage') {
                return;
            }

            if ($orderDiscount->type !== 'amount') {
                throw new Exception('Unknown discount type: ' . $orderDiscount->type);
            }

            $this->insertPrice($orderDiscount->value, $id);

            DB::table('order_discounts')
                ->where('id', $id)
                ->update(['value' => null]);
        });

        Schema::table('order_discounts', function (Blueprint $table) {
            $table->dropColumn('type');
            $table->renameColumn('value', 'percentage');
            $table->uuid('id')->change();
        });

        Schema::table('order_discounts', function (Blueprint $table) {
            $table->decimal('percentage', 7, 4)->nullable()->change();
        });
    }

    public function down(): void
    {
        $valueColumn = fn (Blueprint $table) => $table->float('value', 19, 4);

        Schema::table('order_discounts', function (Blueprint $table) {
            $table->string('type', 255);
            $table->renameColumn('percentage', 'value');
        });

        Schema::table('order_discounts', function (Blueprint $table) use ($valueColumn) {
            $valueColumn($table)->nullable()->change();
        });

         DB::table('order_discounts')->lazyById()->each(function (object $discount) {
            $amountValue = $this->getPrice('value', $discount->id);

            $data = [
                'type' => $amountValue ? 'amount' : 'percentage',
                'value' => $amountValue ?: $discount->value,
            ];

            DB::table('order_discounts')
                ->where('id', $discount->id)
                ->update($data);

            $this->deletePrice('value', $discount->id);
        });

        Schema::table('order_discounts', function (Blueprint $table) use ($valueColumn) {
            $valueColumn($table)->nullable(false)->change();
            $table->dropColumn('id');
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
            'id' => Uuid::uuid4(),
            'model_id' => $modelId,
            'model_type' => Discount::class,
            'price_type' => 'value',
            'value' => Money::of($value, $currency, roundingMode: RoundingMode::HALF_UP)->getMinorAmount(),
            'currency' => $currency,
        ]);
    }
};
