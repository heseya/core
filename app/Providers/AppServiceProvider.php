<?php

namespace App\Providers;

use App\Services\AnalyticsService;
use App\Services\AppService;
use App\Services\Contracts\AnalyticsServiceContract;
use App\Services\Contracts\AppServiceContract;
use App\Services\Contracts\MediaServiceContract;
use App\Services\Contracts\NameServiceContract;
use App\Services\Contracts\SchemaServiceContract;
use App\Services\Contracts\SettingsServiceContract;
use App\Services\MediaService;
use App\Services\NameService;
use App\Services\SchemaService;
use App\Services\SettingsService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    private const CONTRACTS = [
        AnalyticsServiceContract::class => AnalyticsService::class,
        AppServiceContract::class => AppService::class,
        NameServiceContract::class => NameService::class,
        MediaServiceContract::class => MediaService::class,
        SchemaServiceContract::class => SchemaService::class,
        SettingsServiceContract::class => SettingsService::class,
    ];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        foreach (self::CONTRACTS as $abstract => $concrete) {
            $this->app->bind($abstract, $concrete);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
    }
}
