<?php

use App\Enums\SchemaType;
use App\Models\Option;
use App\Models\Price;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Schema as SchemaModel;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $reflection = new ReflectionClass(Option::class);

        SchemaModel::with(['options', 'prices'])->each(static function ($record) use ($reflection) {
            /** @var SchemaModel $record */
            if ($record->type->is(SchemaType::BOOLEAN)) {
                $optionYes = $record->options()->create([
                    'name' => 'Tak',
                ]);

                $record->prices()->each(static function (Price $price) use ($optionYes, $reflection) {
                    $price->update([
                        'model_id' => $optionYes->getKey(),
                        'model_type' => $reflection->getShortName(),
                    ]);
                });

                $record->options()->create([
                    'name' => 'Nie',
                ]);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
