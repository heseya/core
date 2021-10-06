<?php

namespace App\Events;

use App\Models\Model;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;

abstract class WebHookEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    abstract public function getData(): array;

    public function getIssuer(): Model
    {
        return Auth::user();
    }

    public function isHidden(): bool
    {
        return false;
    }
}
