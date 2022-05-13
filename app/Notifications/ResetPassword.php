<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Lang;

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

        return $this->buildMailMessage($url);
    }

    /**
     * Get the reset password notification mail message for the given URL.
     */
    protected function buildMailMessage(string $url): MailMessage
    {
        return (new MailMessage())
            ->subject(Lang::get('Wniosek o zmianę hasła'))
            ->line('Witaj,')
            ->line('Otrzymaliśmy informację o potrzebie zmiany hasła do Twojego konta.')
            ->line('Aby zmienić hasło kliknij w link ')
            ->action(Lang::get('zmień hasło'), $url)
            ->line(
                'Jeśli zgłoszenie nie pochodzi od Ciebie zignoruj tego maila,
                a Twoje hasło dostępu pozostanie bez zmian.'
            );
    }
}
