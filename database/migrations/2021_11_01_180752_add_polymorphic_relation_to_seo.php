<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPolymorphicRelationToSeo extends Migration
{
    public function up(): void
    {
        Schema::table('seo_metadata', function (Blueprint $table) {
            $table->uuid('model_id')->nullable();
            $table->string('model_type')->nullable();
            $table->index(['model_id', 'model_type'], 'seo_metadata_model_id_model_type_index');

            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('seo_metadata', function (Blueprint $table) {
            $table->dropColumn('model_id');
            $table->dropColumn('model_type');
        });
    }
}
