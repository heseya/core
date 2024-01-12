<?php

namespace App\Mail;

use App\Models\Order;
use App\Traits\ModifyLangFallback;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderStatusUpdated extends Mailable
{
    use ModifyLangFallback;
    use Queueable;
    use SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        private readonly Order $order,
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __(
                'mail.subject-status-changed',
                ['number' => $this->order->code],
                $this->locale,
            ),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $previousSettings = $this->getCurrentLangFallbackSettings();
        $this->setAnyLangFallback();
        $status = $this->order->status?->getTranslation('name', $this->locale);
        $this->setLangFallbackSettings(...$previousSettings);

        return new Content(
            view: 'mail.status-change',
            with: [
                'order' => $this->order,
                'status' => $status,
            ],
        );
    }
}
