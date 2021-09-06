<?php

namespace Heseya\Demo;

use Heseya\Demo\Console\Commands\ResetDatabase;

class ServiceProvider extends \Illuminate\Support\ServiceProvider
{
    protected const COMMANDS = [
        ResetDatabase::class,
    ];

    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands(self::COMMANDS);
        }
    }
}
