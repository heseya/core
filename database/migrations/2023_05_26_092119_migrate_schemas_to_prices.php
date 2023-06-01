<?php

declare(strict_types=1);

use App\Models\Schema as SchemaModel;
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
        DB::table('schemas')->lazyById()->each(function (object $schema) {
            $money = Money::of($schema->price, 'PLN', roundingMode: RoundingMode::HALF_UP);

            DB::table('prices')->insert([
                'id' => Str::uuid(),
                'model_id' => $schema->id,
                'model_type' => SchemaModel::class,
                'value' => $money->getMinorAmount(),
            ]);
        });

        Schema::table('schemas', function (Blueprint $table) {
            $table->dropColumn('price');
        });
    }

    public function down(): void
    {
        Schema::table('schemas', function (Blueprint $table) {
            $table->float('price', 19, 4)->default(0);
        });

        DB::table('schemas')->lazyById()->each(function (object $schema) {
            $price = DB::table('prices')
                ->where('model_id', $schema->id)
                ->first();

            $money = Money::of($price->value, 'PLN');

            DB::table('schemas')
                ->where('id', $schema->id)
                ->update(['price' => $money->getAmount()]);
        });
    }
};
