<?php

use App\Models\App;
use App\Services\Contracts\UrlServiceContract;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\App as AppFacade;
use Illuminate\Support\Facades\Schema;

class MakeAppsUrlUnique extends Migration
{
    public function up(): void
    {
        /** @var UrlServiceContract $urlService */
        $urlService = AppFacade::make(UrlServiceContract::class);

        foreach (App::all() as $app) {
            $app->update([
                'url' => $urlService->normalizeUrl($app->url),
            ]);
        }

        Schema::table('apps', function (Blueprint $table) {
            $table->string('url')->unique()->change();
        });
    }

    public function down(): void
    {
        Schema::table('apps', function (Blueprint $table) {
            $table->dropUnique('apps_url_unique');
        });
    }
}
