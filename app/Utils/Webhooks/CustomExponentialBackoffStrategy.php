<?php

namespace App\Utils\Webhooks;

use Spatie\WebhookServer\BackoffStrategy\ExponentialBackoffStrategy;

class CustomExponentialBackoffStrategy extends ExponentialBackoffStrategy
{
    public function waitInSecondsAfterAttempt(int $attempt): int
    {
        if ($attempt > 3) {
            return 1000;
        }

        return 10 ** $attempt;
    }
}
