<?php

return [

    'enabled' => env('FURGONETKA_ENABLED', false),

    'webhook_salt' => env('FURGONETKA_WEBHOOK_SALT', 'tajnasól'),

    'login' => env('FURGONETKA_LOGIN', 'login@example.com'),

    'password' => env('FURGONETKA_PASSWORD', 'secret'),

    'auth_url' => env('FURGONETKA_AUTH_URL', 'https://konto-test.furgonetka.pl'),

    'api_url' => env('FURGONETKA_API_URL', 'http://biznes-test.furgonetka.pl/api/soap/v2?wsdl'),

    'client_id' => env('FURGONETKA_CLIENT_ID', 'client-id'),

    'client_secret' => env('FURGONETKA_CLIENT_SECRET', 'clientsecret'),

];
