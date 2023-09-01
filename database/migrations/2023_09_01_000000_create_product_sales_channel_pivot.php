<?php

use App\Models\Product;
use Domain\Product\Models\ProductSalesChannel;
use Domain\SalesChannel\Models\SalesChannel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create((new ProductSalesChannel())->getTable(), function (Blueprint $table) {
            $table->uuid('sales_channel_id')->index();
            $table->uuid('product_id')->index();

            $table->boolean('active')->default(true);
            $table->boolean('public')->default(true);

            $table->foreign('sales_channel_id')->references('id')->on((new SalesChannel())->getTable())->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on((new Product())->getTable())->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists((new ProductSalesChannel())->getTable());
    }
};
