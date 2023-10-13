<?php

namespace App\Notifications;

use App\Traits\GetLocale;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Lang;

class TFARecoveryCodes extends Notification
{
    use Queueable;
    use GetLocale;

    public function __construct()
    {
        $this->locale = $this->getLocaleFromRequest();
    }

    public function via(mixed $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        /** @var string $subject */
        $subject = Lang::get('mail.subject-recovery-codes', [], $this->locale);

        return (new MailMessage())
            ->subject($subject)
            ->view('mail.tfa-recovery-codes');
    }
}
