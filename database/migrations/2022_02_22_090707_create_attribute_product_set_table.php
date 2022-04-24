<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAttributeProductSetTable extends Migration
{
    public function up(): void
    {
        Schema::create('attribute_product_set', function (Blueprint $table): void {
            $table->uuid('attribute_id')->index();
            $table->uuid('product_set_id')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attribute_product_set');
    }
}
