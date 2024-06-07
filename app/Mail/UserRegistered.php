<?php

declare(strict_types=1);

namespace App\Mail;

use App\Enums\UserRegisteredTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Config;

final class UserRegistered extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        readonly private string $user_name,
        readonly private UserRegisteredTemplate $template = UserRegisteredTemplate::DEFAULT,
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __(
                match ($this->template) {
                    UserRegisteredTemplate::CLIENT_PARTNER => 'mail.client.partner-register.subject',
                    UserRegisteredTemplate::CLIENT_INSIDER => 'mail.client.user-register.subject',
                    default => 'mail.subject-user-registered',
                },
                locale: $this->locale,
            ),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        [$view, $url] = match ($this->template) {
            UserRegisteredTemplate::CLIENT_PARTNER => [
                'mail.client.partner-register',
                Config::get('app.admin_url'),
            ],
            UserRegisteredTemplate::CLIENT_INSIDER => [
                'mail.client.user-register',
                Config::get('app.store_url'),
            ],
            default => [
                'mail.user-registered',
                null,
            ],
        };

        return new Content(
            $view,
            with: [
                'name' => $this->user_name,
                'url' => $url,
            ],
        );
    }
}
