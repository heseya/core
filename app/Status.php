<?php

namespace App;

class Status
{
    public $payment_status = [
        0 => [
            'name' => 'Oczekuje',
            'color' => 'grey',
        ],
        1 => [
            'name' => 'Realizacja',
            'color' => 'orange',
        ],
        2 => [
            'name' => 'Opłacone',
            'color' => 'green',
        ],
        3 => [
            'name' => 'Niepowodzenie',
            'color' => 'red',
        ],
        4 => [
            'name' => 'Przy odbiorze',
            'color' => 'blue',
        ],
    ];

    public $shop_status = [
        0 => [
            'name' => 'Oczekuje',
            'color' => 'grey',
        ],
        1 => [
            'name' => 'Produkcja',
            'color' => 'blue',
        ],
        2 => [
            'name' => 'Realizacja',
            'color' => 'orange',
        ],
        3 => [
            'name' => 'Gotowe',
            'color' => 'green',
        ],
        4 => [
            'name' => 'Anulowano',
            'color' => 'red',
        ],
    ];

    public $delivery_status = [
        0 => [
            'name' => 'Oczekuje',
            'color' => 'grey',
            'description' => 'Przesyłka oczekuje na nadanie',
        ],
        1 => [
            'name' => 'Zamówione',
            'color' => 'blue',
            'description' => 'Przesyłka została zamówiona u dostawcy i czeka na odbiór',
        ],
        2 => [
            'name' => 'W trasie',
            'color' => 'orange',
            'description' => '',
        ],
        3 => [
            'name' => 'W doręczeniu',
            'color' => 'orange',
            'description' => 'Przesyłka zmierza już prosto do Ciebie!',
        ],
        4 => [
            'name' => 'Dostarczono',
            'color' => 'green',
            'description' => '',
        ],
        5 => [
            'name' => 'Anulowano',
            'color' => 'red',
            'description' => '',
        ],
        6 => [
            'name' => 'Zwrot do nadawcy',
            'color' => 'red',
            'description' => '',
        ],
        7 => [
            'name' => 'Problem z odbiorem',
            'color' => 'red',
            'description' => 'Występuje problem z odbiorem przesyłki od nadawcy',
        ],
        8 => [
            'name' => 'Problem z doręczeniem',
            'color' => 'red',
            'description' => 'Występuje problem w trakcie doręczenia przesyłki',
        ],
        9 => [
            'name' => 'Nieoczekiewany problem',
            'color' => 'red',
            'description' => 'Wystąpiła nieoczekiwana sytuacja',
        ],
    ];

    public $furgonetka_status = [
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
