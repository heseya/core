<?php

use Illuminate\Support\Env;

return [
    'cipher' => Env::get('WEBHOOK_CIPHER', 'AES-256-CBC'),
    'key' => Env::get('WEBHOOK_KEY'),
    'display_info' => Env::get('WEBHOOK_INFO', false),
];
