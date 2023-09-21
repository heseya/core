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
        if (Schema::hasTable('categories') && Schema::hasColumn('categories', 'order')) {
            Schema::table('categories', function (Blueprint $table): void {
                $table->dropColumn('order');
            });
        }

        if (Schema::hasTable('brands') && Schema::hasColumn('brands', 'order')) {
            Schema::table('brands', function (Blueprint $table): void {
                $table->dropColumn('order');
            });
        }

        if (Schema::hasTable('product_schemas') && Schema::hasColumn('product_schemas', 'order')) {
            Schema::table('product_schemas', function (Blueprint $table): void {
                $table->dropColumn('order');
            });
        }

        if (Schema::hasTable('shipping_methods') && Schema::hasColumn('shipping_methods', 'order')) {
            Schema::table('shipping_methods', function (Blueprint $table): void {
                $table->dropColumn('order');
            });
        }

        if (Schema::hasTable('statuses') && Schema::hasColumn('statuses', 'order')) {
            Schema::table('statuses', function (Blueprint $table): void {
                $table->dropColumn('order');
            });
        }
    }
}
