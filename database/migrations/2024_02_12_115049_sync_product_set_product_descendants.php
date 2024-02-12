<?php

use Domain\ProductSet\ProductSetService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        /** @var ProductSetService $service */
        $service = app(ProductSetService::class);

        $service->attachAllProductsToAncestorSets();
    }

    public function down(): void
    {
        //
    }
};
