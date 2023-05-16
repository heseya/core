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
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    protected bool $encrypted;
    protected string $triggered_at;
    protected Authenticatable|Model|Pivot|null $issuer;

    public function __construct()
    {
        $user = Auth::user();

        $this->encrypted = false;
        $this->triggered_at = Carbon::now()->format('c');
        // Check if the user is logged in and has an ID (Unauthorized user doesn't have).
        $this->issuer = $user?->getAuthIdentifier() ? $user : null;
    }

    public function getData(): array
    {
        return [
            'event' => $this->getEvent(),
            'triggered_at' => $this->triggered_at,
            'issuer_type' => $this->issuer
                ? IssuerType::getValue(strtoupper($this->getModelClass($this->issuer)))
                : IssuerType::UNAUTHENTICATED,
            'api_url' => Config::get('app.url'),
            'data_type' => $this->getDataType(),
            'data' => $this->getDataContent(),
        ];
    }

    abstract public function getDataContent(): array;

    abstract public function getDataType(): string;

    public function getIssuer(): Model|Authenticatable|Pivot|null
    {
        return $this->issuer;
    }

    public function isHidden(): bool
    {
        return false;
    }

    public function isEncrypted(): bool
    {
        return $this->encrypted;
    }

    public function getEvent(): string
    {
        return Str::remove('App\\Events\\', $this::class);
    }

    protected function getModelClass(Model|Pivot|Authenticatable $model): string
    {
        return Str::remove('App\\Models\\', $model::class);
    }
}
