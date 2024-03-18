<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeDecimalInSchemasTable extends Migration
{
    public function up(): void
    {
        Schema::table('schemas', function (Blueprint $table): void {
            $table->float('step', 12, 8)->unsigned()->change();
            $table->float('min', 16, 8)->change();
            $table->float('max', 16, 8)->change();
        });
    }
}
