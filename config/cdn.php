<?php

return [
    'host' => rtrim(env('CDN_HOST', 'https://cdn.example.com'), '/'),
    'headers' => [
        'Authorization' => 'Bearer ' . env('CDN_KEY', 'secret'),
    ],
];
