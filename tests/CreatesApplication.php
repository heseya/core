<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Artisan;

trait CreatesApplication
{
    /**
     * Commands that are executed before testing.
     *
     * @var array<string>
     */
    private array $commands = [
        'clear-compiled',
        'cache:clear',
        'view:clear',
        'config:clear',
        'route:clear',
    ];

    /**
     * Creates the application.
     */
    public function createApplication(): Application
    {
        $createApp = function () {
            $app = require __DIR__ . '/../bootstrap/app.php';
            $app->make(Kernel::class)->bootstrap();

            return $app;
        };

        $app = $createApp();

        if ($app->environment() !== 'testing') {
            $this->runCommands();
            $app = $createApp();
        }

        return $app;
    }

    /**
     * Clears Laravel Cache.
     */
    protected function runCommands(): void
    {
        foreach ($this->commands as $command) {
            Artisan::call($command);
        }
    }
}
