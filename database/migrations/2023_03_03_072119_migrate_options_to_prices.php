<?php

use App\Models\Option;
use Brick\Money\Money;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('options')->lazyById()->each(function (object $option) {
            $money = Money::of($option->price, 'PLN');

            DB::table('prices')->insert([
                'id' => Str::uuid(),
                'model_id' => $option->id,
                'model_type' => Option::class,
                'value' => $money->getMinorAmount(),
            ]);
        });

        Schema::table('options', function (Blueprint $table) {
            $table->dropColumn('price');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('options', function (Blueprint $table) {
            $table->float('price', 19, 4)->default(0);
        });

        DB::table('options')->lazyById()->each(function (object $option) {
            $price = DB::table('prices')
                ->where('model_id', $option->id)
                ->first();

            $money = Money::of($price->value, 'PLN');

            DB::table('options')
                ->where('id', $option->id)
                ->update(['price' => $money->getAmount()]);
        });
    }
};
