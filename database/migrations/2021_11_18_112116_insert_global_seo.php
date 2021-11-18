<?php

use App\Models\SeoMetadata;
use Illuminate\Database\Migrations\Migration;

class InsertGlobalSeo extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        SeoMetadata::firstOrCreate([
            'global' => true,
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        SeoMetadata::where('global', 1)->delete();
    }
}
