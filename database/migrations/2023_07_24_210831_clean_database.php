<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::dropIfExists('package_templates');
        Schema::dropIfExists('order_logs');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('order_logs', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('order_id')->index();
            $table->string('content');
            $table->string('user');
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
        });

        Schema::create('package_templates', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->float('weight');
            $table->integer('width');
            $table->integer('height');
            $table->integer('depth');
            $table->timestamps();
        });
    }
};
