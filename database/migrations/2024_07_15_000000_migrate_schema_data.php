<?php

use App\Enums\SchemaType;
use App\Models\Option;
use App\Models\Price;
use Illuminate\Database\Migrations\Migration;
use App\Models\Schema as DeprecatedSchema;
use Domain\Currency\Currency;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $reflection = new ReflectionClass(Option::class);

        DeprecatedSchema::with(['options', 'prices'])->each(static function ($record) use ($reflection) {
            /** @var DeprecatedSchema $record */
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
            if ($record->type->is(SchemaType::SELECT)) {
                $record->options()->each(static function (Option $option) use ($record) {
                    $option->prices()->each(static function (Price $price) use ($record) {
                        $price->update([
                            'value' => $price->value->plus($record->getPriceForCurrency(Currency::fromName($price->currency)))
                        ]);
                    });
                });
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
