<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMetadataTable extends Migration
{
    public function up(): void
    {
        Schema::create('metadata', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('value');
            $table->string('value_type');
            $table->uuidMorphs('model');
            $table->boolean('public')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('metadata');
    }
}
