<?php

namespace App\Models;

class Status
{
    public static function payment($type)
    {
        return [
            0 => [
                'name' => 'Oczekuje',
                'color' => 'grey',
                'icon' => 'lnr-hourglass',
            ],
            1 => [
                'name' => 'Opłacone',
                'color' => 'green',
                'icon' => 'lnr-checkmark-circle',
            ],
            2 => [
                'name' => 'Przy odbiorze',
                'color' => 'blue',
                'icon' => 'lnr-hourglass',
            ],
        ][$type];
    }

    public static function shopAll()
    {
        return [
            0 => [
                'name' => 'Oczekuje',
                'color' => 'grey',
                'icon' => 'lnr-hourglass',
            ],
            1 => [
                'name' => 'Realizacja',
                'color' => 'orange',
                'icon' => 'lnr-arrow-right-circle',
            ],
            2 => [
                'name' => 'Gotowe',
                'color' => 'green',
                'icon' => 'lnr-checkmark-circle',
            ],
            3 => [
                'name' => 'Anulowano',
                'color' => 'red',
                'icon' => 'lnr-cross-circle',
            ],
        ];
    }

    public static function shop($type)
    {
        return self::shopAll()[$type];
    }

    public static function delivery($type)
    {
        return [
            0 => [
                'name' => 'Oczekuje',
                'color' => 'grey',
                'icon' => 'lnr-hourglass',
            ],
            1 => [
                'name' => 'Zamówione',
                'color' => 'blue',
                'icon' => 'lnr-arrow-up-circle',
            ],
            2 => [
                'name' => 'W trasie',
                'color' => 'orange',
                'icon' => 'lnr-arrow-right-circle',
            ],
            3 => [
                'name' => 'Dostarczono',
                'color' => 'green',
                'icon' => 'lnr-checkmark-circle',
            ],
            4 => [
                'name' => 'Anulowano',
                'color' => 'red',
                'icon' => 'lnr-cross-circle',
            ],
            5 => [
                'name' => 'Zwrot do nadawcy',
                'color' => 'red',
                'icon' => 'lnr-undo',
            ],
            6 => [
                'name' => 'Problem z odbiorem',
                'color' => 'red',
                'icon' => 'lnr-warning',
            ],
            7 => [
                'name' => 'Problem z doręczeniem',
                'color' => 'red',
                'icon' => 'lnr-warning',
            ],
            8 => [
                'name' => 'Nieoczekiewany problem',
                'color' => 'red',
                'icon' => 'lnr-warning',
            ],
        ][$type];
    }

    public $furgonetka = [
        'waiting' => 0,
        'ordered' => 1,
        'collected' => 2,
        'transit' => 2,
        'delivery' => 3,
        'delivered' => 4,
        'canceled' => 5,
        'returned' => 6,
        'collect-problem' => 7,
        'delivery-problem' => 8,
        'unexpected-situation' => 9,
    ];
}
