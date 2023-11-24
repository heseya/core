<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('price_maps', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('name');
            $table->string('description');
            $table->string('currency');
            $table->boolean('is_net');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_maps');
    }
};
