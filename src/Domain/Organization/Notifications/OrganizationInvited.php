<?php

declare(strict_types=1);

namespace Domain\Organization\Notifications;

use Domain\Organization\Models\OrganizationToken;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Lang;

final class OrganizationInvited extends Notification
{
    public function __construct(
        private OrganizationToken $organizationToken,
        private string $redirect_url,
        string $preferred_locale,
    ) {
        $this->locale = $preferred_locale;
    }

    /**
     * @return string[]
     */
    public function via(mixed $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        /** @var string $subject */
        $subject = Lang::get('mail.subject-organization-invited', [], $this->locale);

        $params = http_build_query([
            'organization_token' => $this->organizationToken->token,
            'email' => $this->organizationToken->email,
        ]);

        return (new MailMessage())
            ->subject($subject)
            ->view('mail.organization-invited', [
                'url' => $this->redirect_url,
                'params' => $params,
                'organization' => $this->organizationToken->organization,
            ]);
    }
}
