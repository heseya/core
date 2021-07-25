<?php

namespace App\Payments;

use App\Models\Payment;
use Illuminate\Http\Request;

interface PaymentMethod
{
    /**
     * Metoda generowania Url dla danego operatora.
     * Zwraca tablice zmiennych które są później przypisywane do płatności.
     */
    public static function generateUrl(Payment $payment): array;

    /**
     * @return mixed
     */
    public static function translateNotification(Request $request);
}
