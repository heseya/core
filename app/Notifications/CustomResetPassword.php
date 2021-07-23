<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Lang;

/**
 * Class CustomResetPassword
 *
 * @see https://laracasts.com/discuss/channels/laravel/trying-to-setup-a-new-notification-for-updating-user-password
 * @see https://laracasts.com/discuss/channels/laravel/how-to-override-the-tomail-function-in-illuminateauthnotificationsresetpasswordphp
 */
class CustomResetPassword extends Notification
{
    /**
     * The password reset token.
     */
    public $token;

    /**
     * Create a new notification instance.
     */
    public function __construct($token)
    {
        $this->token = $token;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable)
    {
        $param = http_build_query([
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);
        $url = config('app.admin_url') . '/new-password?' . $param;

        return $this->buildMailMessage($url);
    }

    /**
     * Get the reset password notification mail message for the given URL.
     */
    protected function buildMailMessage($url)
    {
        return (new MailMessage())
            ->subject(Lang::get('Reset Password Notification'))
            ->line(
                Lang::get('You are receiving this email because we received a password reset request for your account.')
            )
            ->action(Lang::get('Reset Password'), $url)
            ->line(
                Lang::get('This password reset link will expire in :count minutes.', [
                    'count' => config('auth.passwords.'.config('auth.defaults.passwords').'.expire'),
                ])
            )
            ->line(
                Lang::get('If you did not request a password reset, no further action is required.')
            );
    }
}
