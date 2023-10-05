<?php

use App\Models\Schema;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::query()->where('required', true)->update(['hidden' => false]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
