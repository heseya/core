<?php

declare(strict_types=1);

namespace Domain\Organization\Notifications;

use Domain\Organization\Models\Organization;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Lang;

final class OrganizationRejected extends Notification
{
    public function __construct(
        private Organization $organization,
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
        $subject = Lang::get('mail.subject-organization-rejected', [], $this->locale);

        return (new MailMessage())
            ->subject($subject)
            ->view('mail.organization-rejected', [
                'organization' => $this->organization,
            ]);
    }
}
