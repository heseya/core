<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->decimal('quantity', 16, 4)->nullable();
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

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('product_availabilities');
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('quantity');
        });
    }
};
