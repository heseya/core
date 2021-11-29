<?php

return [

    'store_name' => [
        'value' => 'Heseya Dev Store',
        'public' => true,
    ],

    'store_logo' => [
        'value' => 'https://heseya.com/img/logo.svg',
        'public' => true,
    ],

    'mail_order_created' => [
        'value' => 'Dear customer,<br/>Thank you for placing your order. You will receive a separate email when the status changes.',
        'public' => false,
    ],

    'mail_footer' => [
        'value' => 'Powered by <a href="https://heseya.com">Heseya</a>',
        'public' => false,
    ],

    'order_number_template' => [
        'value' => '{r:6}',
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
        'value' => 'Heseya Dev Store Co.',
        'public' => true,
    ],

    'dashboard_products_contain' => [
        'value' => 0,
        'public' => false,
    ],

    'sender_name' => [
        'value' => null,
        'public' => false,
    ],

    'sender_email' => [
        'value' => null,
        'public' => false,
    ],

    'sender_street' => [
        'value' => null,
        'public' => false,
    ],

    'sender_postcode' => [
        'value' => null,
        'public' => false,
    ],

    'sender_city' => [
        'value' => null,
        'public' => false,
    ],

    'sender_phone' => [
        'value' => null,
        'public' => false,
    ],

    'sender_company' => [
        'value' => null,
        'public' => false,
    ],
];
