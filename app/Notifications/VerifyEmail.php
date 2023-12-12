<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Lang;

class VerifyEmail extends \Illuminate\Auth\Notifications\VerifyEmail
{
    protected function buildMailMessage($url): MailMessage
    {
        /** @var string $subject */
        $subject = Lang::get('mail.subject-verify-email');

        return (new MailMessage())
            ->subject($subject)
            ->view('mail.user-verify-email', [
                'url' => $url,
            ]);
    }
}
