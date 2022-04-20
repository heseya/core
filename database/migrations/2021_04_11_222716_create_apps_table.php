<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAppsTable extends Migration
{
    public function up(): void
    {
        Schema::create('apps', function (Blueprint $table) {
            $table->uuid('id');
            $table->string('name')->nullable();
            $table->string('url');
            $table->string('key');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('apps');
    }
}
