<?php

namespace App\Notifications;

use App\Channels\WebHookChannel;
use App\Enums\IssuerType;
use App\Http\Resources\AppIssuerResource;
use App\Http\Resources\UserIssuerResource;
use App\Models\Model;
use Illuminate\Notifications\Notification;

class WebHookNotification extends Notification
{
    private array $data;
    private Model|null $issuer;

    public function __construct(array $data, Model|null $issuer)
    {
        $this->data = $data;
        $this->issuer = $issuer;
    }

    public function via($notifiable)
    {
        return [WebHookChannel::class];
    }

    public function toWebHook($notifiable)
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
