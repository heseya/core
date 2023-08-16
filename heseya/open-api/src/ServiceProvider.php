<?php

namespace Heseya\OpenApi;

use Heseya\OpenApi\Console\Commands\Generate;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    protected const COMMANDS = [
        Generate::class,
    ];

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands(self::COMMANDS);
        }
    }
}
