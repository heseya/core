<?php

namespace App\Events;

use App\Enums\IssuerType;
use App\Models\Model;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

abstract class WebHookEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    protected string $triggered_at;
    protected Authenticatable|Model|Pivot|null $issuer;

    public function __construct()
    {
        $this->triggered_at = Carbon::now()->format('c');
        $this->issuer = Auth::user()?->getAuthIdentifier() ? Auth::user() : null;
    }

    public function getData(): array
    {
        return [
            'data' => $this->getDataContent(),
            'data_type' => $this->getDataType(),
            'event' => $this->getEvent(),
            'triggered_at' => $this->triggered_at,
            'issuer_type' => $this->issuer ? IssuerType::getValue(strtoupper($this->getModelClass($this->issuer)))
                : IssuerType::UNAUTHENTICATED->value,
            'api_url' => Config::get('app.url'),
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

    public function getEvent(): string
    {
        return Str::remove('App\\Events\\', $this::class);
    }

    protected function getModelClass(Model|Pivot $model): string
    {
        return Str::remove('App\\Models\\', $model::class);
    }
}
