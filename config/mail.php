<?php

return [

    /*
    |--------------------------------------------------------------------------
    | SMTP Mail - FROM
    |--------------------------------------------------------------------------
    |
    | Drivers: "smtp", "sendmail", "mailgun", "mandrill", "ses",
    |            "sparkpost", "postmark", "log", "array"
    |
     */

    'driver' => 'smtp',

    'host' => env('EMAIL_HOST', 'mail.heseya.com'),
    'port' => env('EMAIL_PORT', 587),
    'encryption' => env('EMAIL_ENCRYPTION', 'tls'),
    'username' => env('EMAIL_USER', 'shop@kupdepth.pl'),
    'password' => env('EMAIL_PASSWORD', 'secret'),
    'address' => env('EMAIL_ADDRESS', 'shop@kupdepth.pl'),
    'name' => env('EMAIL_NAME', 'Depth'),

    'from' => [
        'address' => env('EMAIL_ADDRESS', 'shop@kupdepth.pl'),
        'name' => env('EMAIL_NAME', 'Depth'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Sendmail System Path
    |--------------------------------------------------------------------------
    |
    | When using the "sendmail" driver to send e-mails, we will need to know
    | the path to where Sendmail lives on this server. A default path has
    | been provided here, which will work well on most of your systems.
    |
     */

    'sendmail' => '/usr/sbin/sendmail -bs',

    /*
    |--------------------------------------------------------------------------
    | Markdown Mail Settings
    |--------------------------------------------------------------------------
    |
    | If you are using Markdown based email rendering, you may configure your
    | theme and component paths here, allowing you to customize the design
    | of the emails. Or, you may simply stick with the Laravel defaults!
    |
     */

    'markdown' => [
        'theme' => 'default',

        'paths' => [
            resource_path('views/vendor/mail'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Log Channel
    |--------------------------------------------------------------------------
    |
    | If you are using the "log" driver, you may specify the logging channel
    | if you prefer to keep mail messages separate from other log entries
    | for simpler reading. Otherwise, the default channel will be used.
    |
     */

    'log_channel' => env('MAIL_LOG_CHANNEL'),

];
