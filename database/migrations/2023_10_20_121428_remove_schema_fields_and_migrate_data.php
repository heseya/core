<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('schemas', function (Blueprint $table): void {
            $table->dropColumn('type');
            $table->dropColumn('pattern');
            $table->dropColumn('validation');
            $table->dropColumn('price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schemas', function (Blueprint $table): void {
            $table->unsignedTinyInteger('type')->default(0);
            $table->float('price', 19, 4)->default(0);
            $table->string('pattern')->nullable();
            $table->string('validation')->nullable();
        });
    }
};
