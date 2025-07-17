<?php

namespace App\Console\Commands;

use App\Models\Media;
use App\Services\Contracts\MediaServiceContract;
use Illuminate\Console\Command;

class MediaCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'media:cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(MediaServiceContract $mediaService): void
    {
        $query = Media::query();

        foreach ($query->cursor() as $media) {
            $mediaService->addMediaToCache($media);
        }
    }
}
