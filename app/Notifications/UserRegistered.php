<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Lang;

class UserRegistered extends Notification
{
    use Queueable;

    public function via(mixed $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        if (Config::get('client.mails')) {
            if ($notifiable?->metadataPersonal->where('name', 'partner')?->first()?->value) {
                /** @var string $subject */
                $subject = Lang::get('mail.client.partner-register.subject');
                $view = 'mail.client.partner-register';
                $url = Config::get('app.admin_url');
            } else {
                /** @var string $subject */
                $subject = Lang::get('mail.client.user-register.subject');
                $view = 'mail.client.user-register';
                $url = Config::get('app.store_url');
            }

            return (new MailMessage())
                ->subject($subject)
                ->view($view, [
                    'name' => $notifiable?->name,
                    'url' => $url,
                ]);
        }

        /** @var string $subject */
        $subject = Lang::get('mail.subject-user-registered');

        return (new MailMessage())
            ->subject($subject)
            ->view('mail.user-registered');
    }
}
