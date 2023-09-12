<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeOrderTypeForShippingMethodsTable extends Migration
{
    public function up(): void
    {
        Schema::table('shipping_methods', function (Blueprint $table): void {
            $table->smallInteger('order')->change();
        });
    }

    public function down(): void
    {
//        Schema::table('shipping_methods', function (Blueprint $table): void {
//            $table->unsignedTinyInteger('order')->default(0)->change();
//        });
    }
}
