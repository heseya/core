<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAttributeOptionsTable extends Migration
{
    public function up(): void
    {
        Schema::create('attribute_options', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name')->nullable();
            $table->integer('index');
            $table->float('value_number')->nullable()->default(null);
            $table->date('value_date')->nullable()->default(null);
            $table->foreignUuid('attribute_id')->references('id')->on('attributes')->onDelete('cascade')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attribute_options');
    }
}
