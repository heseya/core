<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNoIndexToSeoMetadataTable extends Migration
{
    public function up(): void
    {
        Schema::table('seo_metadata', function (Blueprint $table): void {
            $table->boolean('no_index')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('seo_metadata', function (Blueprint $table): void {
            $table->dropColumn('no_index');
        });
    }
}
