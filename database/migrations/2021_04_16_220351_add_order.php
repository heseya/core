<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddOrder extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table): void {
            $table->unsignedTinyInteger('order')->default(0);
        });

        Schema::table('brands', function (Blueprint $table): void {
            $table->unsignedTinyInteger('order')->default(0);
        });

        Schema::table('product_schemas', function (Blueprint $table): void {
            $table->unsignedTinyInteger('order')->default(0);
        });

        Schema::table('shipping_methods', function (Blueprint $table): void {
            $table->unsignedTinyInteger('order')->default(0);
        });

        Schema::table('statuses', function (Blueprint $table): void {
            $table->unsignedTinyInteger('order')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table): void {
            $table->dropColumn('order');
        });

        Schema::table('brands', function (Blueprint $table): void {
            $table->dropColumn('order');
        });

        Schema::table('product_schemas', function (Blueprint $table): void {
            $table->dropColumn('order');
        });

        Schema::table('shipping_methods', function (Blueprint $table): void {
            $table->dropColumn('order');
        });

        Schema::table('statuses', function (Blueprint $table): void {
            $table->dropColumn('order');
        });
    }
}
