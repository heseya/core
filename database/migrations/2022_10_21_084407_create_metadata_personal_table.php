<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('metadata_personals', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('value');
            $table->string('value_type');
            $table->uuidMorphs('model');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('metadata_personals');
    }
};
