<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSchemaRelations extends Migration
{
    public function up(): void
    {
        Schema::create('schema_used_schemas', function (Blueprint $table): void {
            $table->uuid('schema_id')->index();
            $table->uuid('used_schema_id')->index();

            $table->primary(['schema_id', 'used_schema_id']);

            $table->foreign('schema_id')->references('id')->on('schemas')->onDelete('cascade');
            $table->foreign('used_schema_id')->references('id')->on('schemas')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schema_used_schemas');
    }
}
