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
        ]
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
            'description' => 'Przesyłka oczekuje na zamówienie/nadanie',
        ],
        1 => [
            'name' => 'Zamówione',
            'color' => 'blue',
            'description' => 'Przesyłka została zamówiona u kuriera i czeka na odbiór',
        ],
        2 => [
            'name' => 'W trasie',
            'color' => 'orange',
            'description' => '',
        ],
        3 => [
            'name' => 'Dostarczono',
            'color' => 'green',
            'description' => '',
        ],
        4 => [
            'name' => 'Anulowano',
            'color' => 'red',
            'description' => '',
        ],
        5 => [
            'name' => 'Zwrot do nadawcy',
            'color' => 'red',
            'description' => '',
        ],
        6 => [
            'name' => 'Problem z odbiorem',
            'color' => 'red',
            'description' => 'Występuje problem z odbiorem przesyłki od nadawcy',
        ],
        7 => [
            'name' => 'Problem z doręczeniem',
            'color' => 'red',
            'description' => 'Występuje problem w trakcie doręczenia przesyłki',
        ],
        8 => [
            'name' => 'Nieoczekiewany problem',
            'color' => 'red',
            'description' => 'Wystąpiła nieoczekiwana sytuacja',
        ]
    ];
}