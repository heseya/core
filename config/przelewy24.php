<?php

return [

    'url' => env('PRZELEWY24_URL', 'https://sandbox.przelewy24.pl/api/v1'),

    'merchant_id' => (int) env('PRZELEWY24_MERCHANT_ID', '11111'),

    'pos_id' => (int) env('PRZELEWY24_POS_ID', '11111'),

    'secret_id' => (int) env('PRZELEWY24_SECRET_ID'),

    'crc' => env('PRZELEWY24_CRC'),

];
