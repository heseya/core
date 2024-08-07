<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::dropIfExists('product_availabilities');

        Schema::table('deposits', function (Blueprint $table): void {
            $table->boolean('from_unlimited')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('deposits', function (Blueprint $table): void {
            $table->dropColumn('from_unlimited');
        });

        Schema::create('product_availabilities', function (Blueprint $table): void {
            $table->uuid('id')->primary()->index();
            $table->uuid('product_id');
            $table->decimal('quantity', 16, 4)->nullable();
            $table->integer('shipping_time')->nullable();
            $table->dateTime('shipping_date')->nullable();

            $table
                ->foreign('product_id')
                ->references('id')
                ->on('products')
                ->onDelete('cascade');
        });
    }
};
