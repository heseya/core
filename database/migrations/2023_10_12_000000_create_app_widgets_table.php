<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('app_widgets', function (Blueprint $table): void {
            $table->uuid('id');
            $table->uuid('app_id')->nullable();

            $table->string('name');
            $table->string('url');
            $table->string('section');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_widgets');
    }
};
