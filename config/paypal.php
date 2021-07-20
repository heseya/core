<?php

return [

    'sandbox' => (bool) env('PAYPAL_SANDBOX', true),
    'client_id' => env('PAYPAL_CLIENT_ID', ''),
    'client_secret' => env('PAYPAL_CLIENT_SECRET', ''),

];
