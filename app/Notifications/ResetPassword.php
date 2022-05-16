<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * @see https://laracasts.com/discuss/channels/laravel/trying-to-setup-a-new-notification-for-updating-user-password
 * @see https://laracasts.com/discuss/channels/laravel/how-to-override-the-tomail-function-in-illuminateauthnotificationsresetpasswordphp
 */
class ResetPassword extends Notification
{
    /**
     * The password reset token.
     */
    public string $token;
    public string $redirect_url;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $token, string $redirect_url)
    {
        $this->token = $token;
        $this->redirect_url = $redirect_url;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(mixed $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(mixed $notifiable): MailMessage
    {
        $param = http_build_query([
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);
        $url = $this->redirect_url . '?' . $param;

        return (new MailMessage())
            ->subject('Wniosek o zmianę hasła')
            ->view('mail.user-password-reset', [
                'url' => $url,
            ]);
    }
}
