<?php

namespace App\Notifications;

use App\Channels\WebHookChannel;
use App\Enums\IssuerType;
use App\Http\Resources\AppIssuerResource;
use App\Http\Resources\UserIssuerResource;
use App\Models\Model;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Notifications\Notification;

class WebHookNotification extends Notification
{
    public function __construct(
        private array $data,
        private Authenticatable|Model|Pivot|null $issuer,
    ) {}

    public function via(mixed $notifiable): array
    {
        return [WebHookChannel::class];
    }

    public function toWebHook(mixed $notifiable): array
    {
        if ($notifiable->with_issuer) {
            $this->data['issuer'] = match ($this->data['issuer_type']) {
                IssuerType::APP => AppIssuerResource::make($this->issuer)->resolve(),
                IssuerType::USER => UserIssuerResource::make($this->issuer)->resolve(),
                default => null,
            };
        }

        return $this->data;
    }
}
