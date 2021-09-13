<?php

namespace Heseya\Demo\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class ResetDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:reset {--file= : SQL file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset Database from SQL file';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        Artisan::call('db:wipe --force');
        $this->info('Database wiped');

        DB::unprepared(file_get_contents($this->option('file') ?? storage_path('demo.sql')));
        $this->info('Backup uploaded');

        Artisan::call('migrate --force');
        $this->info('Migration completed');
    }
}
