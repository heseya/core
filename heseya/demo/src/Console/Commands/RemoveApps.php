<?php

namespace Heseya\Demo\Console\Commands;

use Domain\App\Models\App;
use Domain\App\Services\AppService;
use Exception;
use Illuminate\Console\Command;

class RemoveApps extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'apps:remove {--force : Whether apps should be uninstalled with force}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Uninstall all apps';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(
        private AppService $appService,
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        try {
            $apps = App::all();
            $count = count($apps);

            if ($count === 0) {
                $this->info('Nothing to remove.');

                return;
            }

            $bar = $this->output->createProgressBar($count);

            foreach ($apps as $app) {
                $this->appService->uninstall($app, $this->option('force'));
                $bar->advance();
            }

            $bar->finish();
            $this->info(PHP_EOL . 'Successfully removed all apps!');
        } catch (Exception $exception) {
            $this->error($exception->getMessage());

            return;
        }
    }
}
