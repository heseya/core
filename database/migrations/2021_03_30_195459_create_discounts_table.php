<?php

use App\Enums\DiscountType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDiscountsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('discounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code', 64)->unique();
            $table->string('description')->nullable();
            $table->double('discount', 9, 2);
            $table->unsignedTinyInteger('type')->default(DiscountType::PERCENTAGE);
            $table->unsignedInteger('max_uses')->default(1);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('order_discounts', function (Blueprint $table) {
            $table->uuid('order_id')->index();
            $table->uuid('discount_id')->index();
            $table->double('discount', 9, 2);
            $table->unsignedTinyInteger('type');

            $table->primary(['order_id', 'discount_id']);

            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            $table->foreign('discount_id')->references('id')->on('discounts')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('discounts');
    }
}
