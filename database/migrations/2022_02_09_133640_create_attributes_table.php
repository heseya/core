<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAttributesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('attributes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique()->index();
            $table->string('description')->nullable();
            $table->float('min_number')->nullable();
            $table->float('max_number')->nullable();
            $table->date('min_date')->nullable();
            $table->date('max_date')->nullable();
            $table->string('type');
            $table->boolean('global');
            $table->boolean('sortable');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('attributes');
    }
}
