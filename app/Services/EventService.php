<?php

namespace App\Services;

use App\Enums\EventPermissionType;
use App\Services\Contracts\EventServiceContract;
use Illuminate\Support\Collection;

class EventService implements EventServiceContract
{
    public function index(): Collection
    {
        $events = EventPermissionType::getEventList();
        return collect($events);
    }
}
