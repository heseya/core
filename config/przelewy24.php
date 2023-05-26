<?php

return [
    'url' => env('PRZELEWY24_URL', 'https://sandbox.przelewy24.pl'),

    'merchant_id' => env('PRZELEWY24_MERCHANT_ID', 11111),

    'pos_id' => env('PRZELEWY24_POS_ID', 11111),

    'secret_id' => env('PRZELEWY24_SECRET_ID'),

    'crc' => env('PRZELEWY24_CRC'),
];
