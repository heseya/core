<?php

namespace App\Services;

use App\Enums\EventType;
use App\Services\Contracts\EventServiceContract;
use Illuminate\Support\Collection;

class EventService implements EventServiceContract
{
    public function index(): Collection
    {
        $events = EventType::getEventList();

        return Collection::make($events);
    }
}
