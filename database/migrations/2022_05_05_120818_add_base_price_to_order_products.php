<?php

use App\Models\OrderProduct;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('order_products', function (Blueprint $table): void {
            $table->decimal('base_price', 19, 4)->default(0);
            $table->decimal('base_price_initial', 19, 4)->default(0);
        });

        OrderProduct::chunk(
            100,
            fn ($products) => $products->each(
                function (OrderProduct $product): void {
                    $schemaPrice = 0;
                    foreach ($product->schemas() as $schema) {
                        $schemaPrice += $schema->price_initial;
                    }
                    $product::update([
                        'base_price' => $product->price,
                        'base_price_initial' => $product->price_initial,
                        'price' => $product->price + $schemaPrice,
                        'price_initial' => $product->price_initial + $schemaPrice,
                    ]);
                }
            )
        );
    }

    public function down(): void
    {
        OrderProduct::chunk(
            100,
            fn ($products) => $products->each(
                fn (OrderProduct $product) => $product::update([
                    'price' => $product->base_price,
                    'price_initial' => $product->base_price_initial,
                ])
            )
        );

        Schema::table('order_products', function (Blueprint $table): void {
            $table->dropColumn('base_price');
            $table->dropColumn('base_price_initial');
        });
    }
};
