<?php

namespace App\Notifications;

use App\Models\Order;
use App\Traits\GetLocale;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Lang;

class SendUrls extends Notification
{
    use GetLocale;

    private Order $order;
    private Collection $products;

    public function __construct(Order $order, Collection $products)
    {
        $this->order = $order;
        $this->products = $products;
        $this->locale = $this->getLocaleFromRequest();
    }

    public function via(mixed $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(mixed $notifiable): MailMessage
    {
        /** @var string $subject */
        $subject = Lang::get('mail.subject-send-urls', ['code' => $this->order->code], $this->locale);

        return (new MailMessage())
            ->subject($subject)
            ->view('mail.send-urls', [
                'order' => $this->order,
                'products' => $this->products,
            ]);
    }
}
