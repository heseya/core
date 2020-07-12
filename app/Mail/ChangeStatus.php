<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ChangeStatus extends Mailable
{
    use Queueable, SerializesModels;

    private Order $order;

    /**
     * Create a new message instance.
     *
     * @param Order $order
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this
            ->view('mail.status-change')
            ->subject('Twoje zamówienie zmieniło status')
            ->with([
                'order' => $this->order,
            ]);
    }
}
