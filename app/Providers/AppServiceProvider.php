<?php

namespace App\Providers;

use App\Schemas\SelectSchema;
use App\Services\Contracts\AnalyticsServiceContract;
use App\Services\Contracts\MediaServiceContract;
use App\Services\AnalyticsService;
use App\Services\MediaService;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;

class AppServiceProvider extends ServiceProvider
{
    const CONTRACTS = [
        AnalyticsServiceContract::class => AnalyticsService::class,
        MediaServiceContract::class => MediaService::class,
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
