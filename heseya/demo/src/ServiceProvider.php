<?php

namespace Heseya\Demo;

use Heseya\Demo\Console\Commands\RemoveApps;
use Heseya\Demo\Console\Commands\ResetDatabase;
use Heseya\Demo\Console\Commands\WebHookDispatch;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    protected const COMMANDS = [
        ResetDatabase::class,
        RemoveApps::class,
        WebHookDispatch::class,
    ];

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands(self::COMMANDS);
        }
    }
}
