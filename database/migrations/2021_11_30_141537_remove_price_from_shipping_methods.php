<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemovePriceFromShippingMethods extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('shipping_methods', 'price')) {
            Schema::table('shipping_methods', function (Blueprint $table) {
                $table->dropColumn('price');
            });
        }
    }
}
