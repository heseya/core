<?php

declare(strict_types=1);

use App\Models\Order;
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
        DB::table('orders')->lazyById()->each(function (object $order) {
            $this->insertPrice('summary', $order->summary, $order->id);
            $this->insertPrice('shipping_price', $order->shipping_price, $order->id);
            $this->insertPrice('shipping_price_initial', $order->shipping_price_initial, $order->id);
            $this->insertPrice('cart_total', $order->cart_total, $order->id);
            $this->insertPrice('cart_total_initial', $order->cart_total_initial, $order->id);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('summary');
            $table->dropColumn('shipping_price');
            $table->dropColumn('shipping_price_initial');
            $table->dropColumn('cart_total');
            $table->dropColumn('cart_total_initial');
        });
    }

    public function down(): void
    {
        $columnSpec = fn (Blueprint $table, string $name) => $table->float($name, 19, 4);

        Schema::table('orders', function (Blueprint $table) use ($columnSpec) {
            $table->double('summary', 19, 4)->default(0);

            $columnSpec($table, 'shipping_price')->nullable();
            $columnSpec($table, 'shipping_price_initial')->nullable();

            $table->double('cart_total', 19, 4)->default(0);
            $table->double('cart_total_initial', 19, 4)->default(0);
        });

        DB::table('orders')->lazyById()->each(function (object $order) {
            $data = [
                'summary' => $this->getPrice('summary', $order->id),
                'shipping_price' => $this->getPrice('shipping_price', $order->id),
                'shipping_price_initial' => $this->getPrice('shipping_price_initial', $order->id),
                'cart_total' => $this->getPrice('cart_total', $order->id),
                'cart_total_initial' => $this->getPrice('cart_total_initial', $order->id),
            ];

            DB::table('orders')
                ->where('id', $order->id)
                ->update($data);
        });

        Schema::table('orders', function (Blueprint $table) use ($columnSpec) {
            $columnSpec($table, 'shipping_price')->nullable(false)->change();
            $columnSpec($table, 'shipping_price_initial')->nullable(false)->change();
        });
    }

    private function getPrice(string $type, string $modelId): BigDecimal
    {
        $price = DB::table('prices')
            ->where('model_id', $modelId)
            ->where('price_type', $type)
            ->first();

        return Money::of($price->value, 'PLN')->getAmount();
    }

    private function insertPrice(string $type, string|float $value, string $modelId): void
    {
        DB::table('prices')->insert([
            'id' => Str::uuid(),
            'model_id' => $modelId,
            'model_type' => Order::class,
            'price_type' => $type,
            'value' => Money::of($value, 'PLN', roundingMode: RoundingMode::HALF_UP)->getMinorAmount(),
        ]);
    }
};
