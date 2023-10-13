<?php

namespace App\Notifications;

use App\Traits\GetLocale;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Lang;

class TFAInitialization extends Notification
{
    use GetLocale;
    use Queueable;

    private string $code;

    public function __construct(string $code)
    {
        $this->code = $code;
        $this->locale = $this->getLocaleFromRequest();
    }

    public function via(mixed $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        /** @var string $subject */
        $subject = Lang::get('mail.subject-tfa-initialization', [], $this->locale);

        return (new MailMessage())
            ->subject($subject)
            ->view('mail.tfa-initialization', [
                'code' => $this->code,
            ]);
    }
}
