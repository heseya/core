<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCoverToProductSetsTable extends Migration
{
    public function up(): void
    {
        Schema::table('product_sets', function (Blueprint $table): void {
            $table->uuid('cover_id')->nullable();
            $table->foreign('cover_id')->references('id')->on('media')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('product_sets', function (Blueprint $table): void {
            $table->dropForeign('product_sets_cover_id_foreign');
            $table->dropColumn('cover_id');
        });
    }
}
