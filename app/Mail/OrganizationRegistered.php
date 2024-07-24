<?php

namespace App\Mail;

use Domain\Organization\Models\Organization;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrganizationRegistered extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        private readonly Organization $organization,
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        /** @var string $name */
        $name = $this->organization->address?->name;
        return new Envelope(
            subject: __(
                'mail.subject-new-organization',
                ['name' => $name],
                $this->locale,
            ),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'mail.organization-registered',
            with: ['organization' => $this->organization],
        );
    }
}
