<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class SchemaTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_schemas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('product_id')->index();
            $table->uuid('schema_id')->index();
            $table->string('schema_type');
            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
        });

        Schema::create('schemas_boolean', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->float('price', 19, 4);
            $table->json('validation');
            $table->timestamps();
        });

        Schema::create('schemas_text', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->float('price', 19, 4);
            $table->json('validation');
            $table->timestamps();
        });
    }
}
