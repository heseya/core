<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organization_saved_addresses', function (Blueprint $table) {
            $table->foreignUuid('organization_id')->change()->references('id')->on('organizations')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        //
    }
};
