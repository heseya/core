<?php

namespace App\Events;

use App\Enums\IssuerType;
use App\Models\Model;
use Carbon\Carbon;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

abstract class WebHookEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    protected $triggered_at;
    protected $issuer;

    public function __construct()
    {
        $this->triggered_at = Carbon::now()->format('c');
        $this->issuer = Auth::user()->getAuthIdentifier() ? Auth::user() : null;
    }

    public function getData(): array
    {
        return [
            'data' => $this->getDataContent(),
            'data_type' => $this->getDataType(),
            'event' => Str::remove('App\\Events\\', $this::class),
            'triggered_at' => $this->triggered_at,
            'issuer_type' => $this->issuer ? IssuerType::getValue(strtoupper($this->getModelClass($this->issuer)))
                : IssuerType::UNAUTHENTICATED,
        ];
    }

    abstract public function getDataContent(): array;

    abstract public function getDataType(): string;

    public function getIssuer(): Model|null
    {
        return $this->issuer;
    }

    public function isHidden(): bool
    {
        return false;
    }

    protected function getModelClass(Model $model): string
    {
        return Str::remove('App\\Models\\', $model::class);
    }
}
