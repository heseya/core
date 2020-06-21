<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStatusesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('statuses', function (Blueprint $table) {
            $table->tinyIncrements('id');
            $table->string('name', 60);
            $table->string('color', 8);
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['payment_status', 'shop_status', 'delivery_status']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->tinyInteger('status_id')->unsigned()->nullable();
            $table->foreign('status_id')->references('id')->on('statuses')->onDelete('set null');
        });

        DB::table('statuses')->insert([
            'id' => 1,
            'name' => 'Nowe',
            'color' => 'ffd600',
            'description' => 'Twoje zamówienie zostało zapisane w systemie!',
        ]);

        DB::table('statuses')->insert([
            'id' => 2,
            'name' => 'Wysłane',
            'color' => '1faa00',
            'description' => 'Zamówienie zostało wysłane i niedługo znajdzie się w Twoich rękach :)',
        ]);

        DB::table('statuses')->insert([
            'id' => 3,
            'name' => 'Anulowane',
            'color' => 'a30000',
            'description' => 'Twoje zamówienie zostało anulowane, jeśli uważasz, że to błąd, skontaktuj się z nami.',
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['status_id']);
            $table->dropColumn('status_id');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->tinyInteger('payment_status')->default(0);
            $table->tinyInteger('shop_status')->default(0);
            $table->tinyInteger('delivery_status')->default(0);
        });

        Schema::dropIfExists('statuses');
    }
}
