<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTokensTable extends Migration
{
    public function up(): void
    {
        Schema::create('tokens', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->boolean('invalidated')->default(false);
            $table->timestamp('expires_at');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tokens');
    }
}
