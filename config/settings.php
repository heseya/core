<?php

return [

    'store_name' => [
        'value' => 'E-Commerce Dog',
        'public' => true,
    ],

    'order_number_template' => [
        'value' => '{no}',
        'public' => false,
    ],

    'order_number_start' => [
        'value' => 0,
        'public' => false,
    ],

    'bank_transfer_account' => [
        'value' => '00 0000 0000 0000 0000 0000 0000',
        'public' => true,
    ],

    'bank_transfer_address' => [
        'value' => env('APP_NAME'),
        'public' => true,
    ],

    'dashboard_products_contain' => [
        'value' => 0,
        'public' => false,
    ],

];
