<?php

namespace App\Notifications;

use App\Channels\WebHookChannel;
use App\Models\Model;
use Illuminate\Notifications\Notification;

class WebHookNotification extends Notification
{
    private array $data;
    private Model $issuer;

    public function __construct(array $data, Model $issuer)
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
        $this->data['issuer'] = $notifiable->with_issuer ? $this->issuer->toArray() : null;
        return $this->data;
    }
}
