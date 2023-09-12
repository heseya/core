<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeOrderTypeForPagesTable extends Migration
{
    public function up(): void
    {
        Schema::table('pages', static function (Blueprint $table): void {
            $table->smallInteger('order')->change();
        });
    }

    public function down(): void
    {
//        Schema::table('pages', function (Blueprint $table): void {
//            $table->unsignedTinyInteger('order')->default(0)->change();
//        });
    }
}
