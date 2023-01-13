<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SendUrls extends Notification
{
    private Order $order;
    private Collection $products;

    public function __construct(Order $order, Collection $products)
    {
        $this->order = $order;
        $this->products = $products;
    }

    public function via(mixed $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('Produkty cyfrowe zamówienia '. $this->order->code)
            ->view('mail.send-urls', [
                'order' => $this->order,
                'products' => $this->products,
            ]);
    }
}
