<?php

namespace Heseya\GraphQL;

use MLL\GraphQLPlayground\GraphQLPlaygroundServiceProvider;
use Nuwave\Lighthouse\LighthouseServiceProvider;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/lighthouse.php', 'lighthouse');
    }

    public function boot(): void
    {
        $this->app->register(LighthouseServiceProvider::class);

        if (config('app.debug')) {
            $this->app->register(GraphQLPlaygroundServiceProvider::class);
        }
    }
}
