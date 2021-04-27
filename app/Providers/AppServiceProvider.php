<?php

namespace App\Providers;

use App\Services\AnalyticsService;
use App\Services\AppService;
use App\Services\Contracts\AnalyticsServiceContract;
use App\Services\Contracts\AppServiceContract;
use App\Services\Contracts\MediaServiceContract;
use App\Services\Contracts\SchemaServiceContract;
use App\Services\Contracts\SettingsServiceContract;
use App\Services\MediaService;
use App\Services\SchemaService;
use App\Services\SettingsService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    const CONTRACTS = [
        AnalyticsServiceContract::class => AnalyticsService::class,
        AppServiceContract::class => AppService::class,
        MediaServiceContract::class => MediaService::class,
        SchemaServiceContract::class => SchemaService::class,
        SettingsServiceContract::class => SettingsService::class,
    ];

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        foreach (self::CONTRACTS as $abstract => $concrete) {
            $this->app->bind($abstract, $concrete);
        }
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
