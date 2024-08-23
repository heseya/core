<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prices', function (Blueprint $table): void {
            $table->float('net', 19, 4)->nullable();
            $table->float('gross', 19, 4)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('prices', function (Blueprint $table): void {
            $table->dropColumn('net');
            $table->dropColumn('gross');
        });
    }
};
