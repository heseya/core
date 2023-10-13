<?php

namespace App\Notifications;

use App\Enums\UserRegisteredTemplate;
use App\Traits\GetLocale;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Lang;

class UserRegistered extends Notification
{
    use Queueable;
    use GetLocale;

    public function __construct(
        protected UserRegisteredTemplate $template = UserRegisteredTemplate::DEFAULT,
    ) {
        $this->locale = $this->getLocaleFromRequest();
    }

    public function via(mixed $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        [$subject, $view, $url] = match ($this->template) {
            UserRegisteredTemplate::CLIENT_PARTNER => [
                Lang::get('mail.client.partner-register.subject', [], $this->locale),
                'mail.client.partner-register',
                Config::get('app.admin_url'),
            ],
            UserRegisteredTemplate::CLIENT_INSIDER => [
                Lang::get('mail.client.user-register.subject', [], $this->locale),
                'mail.client.user-register',
                Config::get('app.store_url'),
            ],
            default => [
                Lang::get('mail.subject-user-registered', [], $this->locale),
                'mail.user-registered',
                null,
            ],
        };

        if (is_array($subject)) {
            $subject = implode($subject);
        }

        return (new MailMessage())
            ->subject($subject)
            ->view($view, [
                'name' => $notifiable?->name ?? $notifiable?->email,
                'url' => $url,
            ]);
    }
}
