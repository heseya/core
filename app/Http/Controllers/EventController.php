<?php

namespace App\Http\Controllers;

use App\Http\Resources\EventResource;
use App\Services\Contracts\EventServiceContract;
use App\Services\EventService;
use Illuminate\Http\Resources\Json\JsonResource;

class EventController extends Controller
{
    private EventService $eventService;

    public function __construct(EventServiceContract $eventService)
    {
        $this->eventService = $eventService;
    }

    public function index(): JsonResource
    {
        $events = $this->eventService->index();
        return EventResource::collection($events);
    }
}
