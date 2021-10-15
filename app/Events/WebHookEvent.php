<?php

namespace App\Events;

use App\Models\Model;
use Carbon\Carbon;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;

abstract class WebHookEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    protected $triggered_at;
    protected $issuer;

    public function __construct()
    {
        $this->triggered_at = Carbon::now()->format('c');
        $this->issuer = Auth::user();
    }

    abstract public function getData(): array;

    public function getIssuer(): Model
    {
        return $this->issuer;
    }

    public function isHidden(): bool
    {
        return false;
    }
}
