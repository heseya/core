<?php

namespace App\Notifications;

use App\Http\Resources\OrderResource;
use App\Models\Order;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Lang;

class OrderCreated extends Notification
{
    private Order $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
        $this->locale = $order->language;
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
        $subject = Lang::get('mail.subject-new-order', ['number' => $this->order->code], $this->locale);

        return (new MailMessage())
            ->subject($subject)
            ->view('mail.new-order', [
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
