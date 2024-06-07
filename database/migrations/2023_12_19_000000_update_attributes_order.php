<?php

use App\Services\Contracts\ReorderServiceContract;
use Domain\ProductAttribute\Models\Attribute;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        app(ReorderServiceContract::class)->assignOrderToAll(Attribute::class);
    }

    public function down(): void
    {
    }
};
