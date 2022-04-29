<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    public function up(): void
    {
        Schema::table('item_product', function (Blueprint $table): void {
            $table->renameColumn('quantity', 'required_quantity');
        });
    }

    public function down(): void
    {
        Schema::table('item_product', function (Blueprint $table): void {
            $table->renameColumn('required_quantity', 'quantity');
        });
    }
};
