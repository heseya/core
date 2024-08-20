<?php

declare(strict_types=1);

use App\Models\Price;
use Domain\Currency\Currency;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prices', function (Blueprint $table): void {
            $table->uuid('price_map_id')->nullable();
        });

        foreach (Currency::cases() as $currency) {
            Price::where('currency', $currency->value)->update(['price_map_id' => $currency->getDefaultPriceMapId()]);
        }
    }

    public function down(): void {}
};
