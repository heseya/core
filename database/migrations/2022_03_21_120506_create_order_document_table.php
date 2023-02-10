<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('order_document', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->string('name')->nullable();
            $table->foreignUuid('media_id')->index()->references('id')->on('media')->onDelete('cascade');
            $table->foreignUuid('order_id')->index()->references('id')->on('orders')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_document');
    }
};
