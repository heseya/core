<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOrderToOptionsTable extends Migration
{
    public function up(): void
    {
        Schema::table('options', function (Blueprint $table): void {
            $table->unsignedSmallInteger('order')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('options', function (Blueprint $table): void {
            $table->dropColumn('order');
        });
    }
}
