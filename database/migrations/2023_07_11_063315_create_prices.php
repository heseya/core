<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('prices', function (Blueprint $table): void {
            $table->uuid('id');
            $table->uuid('model_id');
            $table->string('model_type');
            $table->string('price_type');
            $table->decimal('value', 27, 0);
            $table->string('currency', 3);
            $table->boolean('is_net')->default(false);

            $table->unique(['model_id', 'price_type', 'currency']);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prices');
    }
};
