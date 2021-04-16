<?php

namespace App\Providers;

use App\Services\AnalyticsService;
use App\Services\Contracts\AnalyticsServiceContract;
use App\Services\Contracts\MediaServiceContract;
use App\Services\Contracts\SchemaServiceContract;
use App\Services\MediaService;
use App\Services\SchemaService;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;

class AppServiceProvider extends ServiceProvider
{
    const CONTRACTS = [
        AnalyticsServiceContract::class => AnalyticsService::class,
        MediaServiceContract::class => MediaService::class,
        SchemaServiceContract::class => SchemaService::class,
    ];

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        Passport::ignoreMigrations();

        $this->injectContract(self::CONTRACTS);
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

    private function injectContract(array $contracts): void
    {
        foreach ($contracts as $abstract => $concrete) {
            $this->app->bind($abstract, $concrete);
        }
    }
}
