<?php

use App\Enums\SchemaType;
use App\Models\Option;
use App\Models\Price;
use Illuminate\Database\Migrations\Migration;
use App\Models\Schema as DeprecatedSchema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $reflection = new ReflectionClass(Option::class);

        DeprecatedSchema::with(['options', 'prices'])->each(static function ($record) use ($reflection) {
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

                $record->type = SchemaType::SELECT;
                $record->save();
            }
        });

        DeprecatedSchema::where('type', '!=', SchemaType::SELECT->value)->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
