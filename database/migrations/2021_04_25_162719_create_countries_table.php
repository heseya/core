<?php

use Database\Seeders\CountriesSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

class CreateCountriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('countries', function (Blueprint $table) {
            $table->char('code', 2)->primary()->index();
            $table->string('name', 64);
        });

        $seeder = new CountriesSeeder;
        $seeder->run();

        Schema::create('shipping_method_country', function (Blueprint $table) {
            $table->char('country_code', 2)->index();
            $table->uuid('shipping_method_id')->index();

            $table->primary(['country_code', 'shipping_method_id']);

            $table->foreign('country_code')->references('code')->on('countries')->onDelete('cascade');
            $table->foreign('shipping_method_id')->references('id')->on('shipping_methods')->onDelete('cascade');
        });

        Schema::table('shipping_methods', function (Blueprint $table) {
            $table->boolean('black_list')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('shipping_methods', function (Blueprint $table) {
            $table->dropColumn('black_list');
        });

        Schema::dropIfExists('shipping_method_country');
        Schema::dropIfExists('countries');
    }
}
