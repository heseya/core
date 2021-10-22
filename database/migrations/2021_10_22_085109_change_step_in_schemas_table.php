<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeStepInSchemasTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('schemas', function (Blueprint $table) {
            $table->unsignedDecimal('step', 12, 8)->change();
            $table->decimal('min', 12, 8)->change();
            $table->decimal('max', 12, 8)->change();
        });
    }
}
