<?php

namespace App\Notifications;

use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Lang;

class OrderStatusUpdated extends Notification
{
    private Order $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
        $this->locale = $order->preferredLocale();
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
        /** @var string $subject */
        $subject = Lang::get('mail.subject-status-changed', ['number' => $this->order->code], $this->locale);
        return (new MailMessage())
            ->subject($subject)
            ->view('mail.status-change', [
                'order' => $this->order,
            ]);
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(mixed $notifiable): array
    {
        return [
            'order' => new OrderResource($this->order),
        ];
    }
}
