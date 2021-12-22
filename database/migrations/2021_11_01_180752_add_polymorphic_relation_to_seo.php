<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPolymorphicRelationToSeo extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('seo_metadata', function (Blueprint $table) {
            $table->uuid('model_id')->nullable();
            $table->string('model_type')->nullable();
            $table->index(['model_id', 'model_type'], 'seo_metadata_model_id_model_type_index');

            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('seo_metadata', function (Blueprint $table) {
            $table->dropColumn('model_id');
            $table->dropColumn('model_type');
        });
    }
}
