<?php

namespace App\Listeners;

use App\Events\WebHookEvent;
use Illuminate\Contracts\Queue\ShouldQueue;

class WebHookEventListener implements ShouldQueue
{
    public function __construct()
    {
    }

    public function handle(WebHookEvent $event)
    {
    }
}
