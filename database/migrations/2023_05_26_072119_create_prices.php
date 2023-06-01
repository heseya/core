<?php

declare(strict_types=1);

use App\Models\Option;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prices', function (Blueprint $table) {
            $table->uuid('id');
            $table->uuid('model_id');
            $table->string('model_type');
            $table->string('price_type')->nullable();
            $table->decimal('value', 27, 0);
            $table->boolean('is_net')->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prices');
    }
};
