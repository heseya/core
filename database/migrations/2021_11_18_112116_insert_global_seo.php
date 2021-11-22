<?php

use App\Models\SeoMetadata;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;

class InsertGlobalSeo extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $seo = SeoMetadata::create([
            'global' => true,
        ]);
        Cache::put('seo.global', $seo);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Cache::forget('seo.global');
        SeoMetadata::where('global', 1)->delete();
    }
}
