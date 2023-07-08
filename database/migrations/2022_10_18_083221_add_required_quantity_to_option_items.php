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
        Schema::table('option_items', function (Blueprint $table): void {
            $table->decimal('required_quantity', 16, 4)->default(1);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('option_items', function (Blueprint $table): void {
            $table->removeColumn('required_quantity');
        });
    }
};
