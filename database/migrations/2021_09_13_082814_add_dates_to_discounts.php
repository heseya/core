<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDatesToDiscounts extends Migration
{
    public function up(): void
    {
        Schema::table('discounts', function (Blueprint $table) {
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('expires_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('discounts', function (Blueprint $table) {
            $table->dropColumn('starts_at');
            $table->dropColumn('expires_at');
        });
    }
}
