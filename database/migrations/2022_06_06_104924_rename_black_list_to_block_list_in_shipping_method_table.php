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
        Schema::table('shipping_methods', function (Blueprint $table): void {
            $table->renameColumn('black_list', 'block_list');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('shipping_methods', function (Blueprint $table): void {
            $table->renameColumn('block_list', 'black_list');
        });
    }
};
