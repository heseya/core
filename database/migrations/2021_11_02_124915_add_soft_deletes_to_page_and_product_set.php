<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSoftDeletesToPageAndProductSet extends Migration
{
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->softDeletes();
        });
        Schema::table('product_sets', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
        Schema::table('product_sets', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
}
