<?php

declare(strict_types=1);

namespace Domain\Organization\Notifications;

use Domain\Organization\Models\Organization;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Lang;

final class OrganizationAccepted extends Notification
{
    public function __construct(
        private Organization $organization,
        private string $token,
        private string $redirect_url,
    ) {
        $this->locale = $organization->preferredLocale();
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
        $subject = Lang::get('mail.subject-organization-accepted', [], $this->locale);

        $params = http_build_query([
            'organization_token' => $this->token,
            'email' => $this->organization->email,
        ]);

        return (new MailMessage())
            ->subject($subject)
            ->view('mail.organization-accepted', [
                'organization' => $this->organization,
                'url' => $this->redirect_url,
                'params' => $params,
            ]);
    }
}
