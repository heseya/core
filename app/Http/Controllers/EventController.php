<?php

namespace App\Http\Controllers;

use App\Http\Resources\EventResource;
use App\Services\Contracts\EventServiceContract;
use Illuminate\Http\Resources\Json\JsonResource;

class EventController extends Controller
{
    public function __construct(
        private EventServiceContract $eventService,
    ) {
    }

    public function index(): JsonResource
    {
        $events = $this->eventService->index();

        return EventResource::collection($events);
    }
}
