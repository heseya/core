<?php

declare(strict_types=1);

use App\Models\Option;
use Brick\Math\RoundingMode;
use Brick\Money\Money;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('options')->lazyById()->each(function (object $option) {
            $money = Money::of($option->price, 'PLN', roundingMode: RoundingMode::HALF_UP);

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

    public function down(): void
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
