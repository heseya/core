<?php

namespace App\Notifications;

use App\Traits\GetLocale;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Lang;

class TFASecurityCode extends Notification
{
    use Queueable;
    use GetLocale;

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
        $subject = Lang::get('mail.subject-security-code', [], $this->locale);

        return (new MailMessage())
            ->subject($subject)
            ->view('mail.tfa-security-code', [
                'code' => $this->code,
            ]);
    }
}
